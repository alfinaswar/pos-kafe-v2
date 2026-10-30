<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\InventoryLog;
use App\Models\CashierShift;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pos-access');
    }

    public function index(): View|RedirectResponse
    {
        $activeShift = CashierShift::where('user_id', auth()->id())->where('status', 'open')->first();

        if (!$activeShift) {
            return redirect()->route('shifts.create')
                ->with('warning', 'Silakan buka shift kasir terlebih dahulu dengan mengisi modal awal sebelum melayani transaksi.');
        }

        $products = Product::with('recipeItems.material')->orderBy('name')->get();
        $categories = Product::select('category')->distinct()->whereNotNull('category')->pluck('category');

        $pettyIn = $activeShift->pettyCashes()->where('type', 'in')->sum('amount');
        $pettyOut = $activeShift->pettyCashes()->where('type', 'out')->sum('amount');
        $expectedCash = $activeShift->start_cash + $activeShift->total_sales_cash + $pettyIn - $pettyOut;

        return view('pos.index', compact('products', 'categories', 'activeShift', 'pettyIn', 'pettyOut', 'expectedCash'));
    }

    // Process POS Transaction + Strict Concurrency FIFO Batch Deduction
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string'
        ]);

        try {
            $sale = DB::transaction(function () use ($request) {
                // 1. Validasi Shift Kasir Aktif dengan Row Locking
                $shift = CashierShift::where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$shift) {
                    throw new \Exception('Tidak ada shift kasir yang aktif! Harap buka shift terlebih dahulu.');
                }

                $totalPrice = 0;
                foreach ($request->items as $item) {
                    $totalPrice += ($item['quantity'] * $item['price']);
                }

                if ($request->payment_amount < $totalPrice) {
                    throw new \Exception('Jumlah uang pembayaran kurang dari total tagihan!');
                }

                $changeAmount = $request->payment_amount - $totalPrice;
                $invoiceNumber = 'INV-' . date('YmdHis') . '-' . rand(100, 999);
                $paymentMethod = $request->input('payment_method', 'cash');

                // 2. Catat Transaksi Penjualan Terhubung ke Shift
                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => auth()->id(),
                    'shift_id' => $shift->id,
                    'total_price' => $totalPrice,
                    'payment_amount' => $request->payment_amount,
                    'change_amount' => $changeAmount,
                    'payment_method' => $paymentMethod,
                ]);

                // 3. Loop Menu Items & Pengurangan Stok Bahan Baku FIFO dengan LockForUpdate
                foreach ($request->items as $item) {
                    $product = Product::with('recipeItems.material')->findOrFail($item['product_id']);
                    $subtotal = $item['quantity'] * $item['price'];

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'subtotal' => $subtotal,
                    ]);

                    // Pemotongan Stok Bahan Baku Otomatis Sesuai Resep (BOM) & FIFO Batch
                    foreach ($product->recipeItems as $recipe) {
                        $neededQty = $recipe->quantity * $item['quantity']; // dalam recipe_unit

                        // Kunci row material untuk hindari race condition
                        $material = Material::where('id', $recipe->material_id)->lockForUpdate()->firstOrFail();

                        if ($material->stock < $neededQty) {
                            throw new \Exception("Stok bahan baku [{$material->name}] tidak mencukupi! Sisa: {$material->stock} {$material->recipe_unit}, Dibutuhkan: {$neededQty} {$material->recipe_unit}.");
                        }

                        // Kunci batch FIFO tertua (remaining_qty > 0)
                        $batches = MaterialBatch::where('material_id', $material->id)
                            ->where('remaining_qty', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->lockForUpdate()
                            ->get();

                        $remainingToDeduct = $neededQty;
                        foreach ($batches as $batch) {
                            if ($remainingToDeduct <= 0) {
                                break;
                            }

                            $takeQty = min($batch->remaining_qty, $remainingToDeduct);
                            $batch->decrement('remaining_qty', $takeQty);
                            $remainingToDeduct -= $takeQty;

                            // Catat log mutasi inventaris per batch
                            InventoryLog::create([
                                'material_id' => $material->id,
                                'batch_id' => $batch->id,
                                'type' => 'out',
                                'quantity' => $takeQty,
                                'stock_after' => max(0, $material->stock - ($neededQty - $remainingToDeduct)),
                                'reference' => 'POS Sale #' . $invoiceNumber . ' (' . $product->name . ' x' . $item['quantity'] . ', Batch #' . ($batch->batch_number ?? $batch->id) . ')',
                            ]);
                        }

                        // Jika ada selisih stok batch (misal batch belum tercatat), tetap potong sisa dari master material
                        $material->decrement('stock', $neededQty);

                        // Jika setelah loop batch masih ada sisa yang belum terpotong dari batch (misal database legacy)
                        if ($remainingToDeduct > 0) {
                            InventoryLog::create([
                                'material_id' => $material->id,
                                'type' => 'out',
                                'quantity' => $remainingToDeduct,
                                'stock_after' => $material->fresh()->stock,
                                'reference' => 'POS Sale #' . $invoiceNumber . ' (' . $product->name . ' x' . $item['quantity'] . ' - Unbatched)',
                            ]);
                        }
                    }
                }

                // 4. Update Akumulasi Penjualan Kasir pada Shift
                if ($paymentMethod === 'cash') {
                    $shift->increment('total_sales_cash', $totalPrice);
                } else {
                    $shift->increment('total_sales_non_cash', $totalPrice);
                }

                $shift->expected_cash = $shift->calculateExpectedCash();
                $shift->save();

                // 5. Catat Activity Log Audit
                ActivityLog::record(
                    'POS_SALE',
                    "Transaksi Kasir {$invoiceNumber} oleh " . auth()->user()->name . " Total: Rp " . number_format($totalPrice, 0, ',', '.') . " ({$paymentMethod})"
                );

                return $sale;
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diproses!',
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'change' => $sale->change_amount,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // Thermal Receipt Print View
    public function receipt(Sale $sale): View
    {
        $sale->load(['user', 'shift.outlet', 'items.product']);
        return view('pos.receipt', compact('sale'));
    }
}
