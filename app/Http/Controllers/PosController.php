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
use App\Models\HeldOrder;
use App\Models\Voucher;
use App\Models\Customer;
use App\Support\Setting;

class PosController extends Controller
{
    public function hold(Request $request): JsonResponse
    {
        $data = $request->validate(['items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.quantity' => ['required', 'integer', 'min:1'], 'items.*.price' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $held = HeldOrder::create(['user_id' => $request->user()->id, 'outlet_id' => $request->user()->outlet_id, 'items' => $data['items'], 'notes' => $data['notes'] ?? null]);
        return response()->json(['id' => $held->id, 'status' => $held->status], 201);
    }

    public function recall(Request $request, HeldOrder $heldOrder): JsonResponse
    {
        abort_unless($heldOrder->user_id === $request->user()->id && $heldOrder->outlet_id === $request->user()->outlet_id, 403);
        abort_unless($heldOrder->status === 'held', 422, 'Order sudah dipanggil atau dibatalkan.');
        $heldOrder->update(['status' => 'recalled']);
        return response()->json($heldOrder->fresh());
    }
    // Daftar order yang sedang di-hold (untuk tombol recall di layar kasir)
    public function heldOrders(Request $request): JsonResponse
    {
        $held = HeldOrder::where('user_id', $request->user()->id)
            ->where('status', 'held')
            ->latest()
            ->get(['id', 'notes', 'created_at'])
            ->map(function ($h) {
                $h->total = collect($h->items ?? [])->sum(fn ($i) => $i['quantity'] * $i['price']);
                return $h;
            });

        return response()->json($held);
    }

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
            'items.*.notes' => 'nullable|string|max:255',
            'items.*.discount' => 'nullable|numeric|min:0',
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:64',
            'voucher_code' => 'nullable|string|max:50',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'redeem_points' => 'nullable|integer|min:0',
        ]);

        try {
            $sale = DB::transaction(function () use ($request) {
                // 0. Cegah transaksi ganda (double-submit): key sudah pernah dipakai?
                $idempotencyKey = $request->input('idempotency_key');
                if ($idempotencyKey) {
                    $existing = Sale::where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        // Kembalikan transaksi yang sudah tersimpan, jangan buat baru.
                        return $existing;
                    }
                }

                // 1. Validasi Shift Kasir Aktif dengan Row Locking
                $shift = CashierShift::where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$shift) {
                    throw new \Exception('Tidak ada shift kasir yang aktif! Harap buka shift terlebih dahulu.');
                }

                // Muat produk dari database — harga divalidasi di server,
                // tidak mempercayai harga yang dikirim browser.
                $products = Product::whereIn('id', collect($request->items)->pluck('product_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $totalPrice = 0;
                $itemDiscountTotal = 0;
                foreach ($request->items as $item) {
                    $product = $products->get($item['product_id']);
                    if (!$product) {
                        throw new \Exception('Produk tidak ditemukan: #' . $item['product_id']);
                    }
                    // Harga server yang menjadi acuan total.
                    $serverPrice = (float) $product->price;
                    if (abs((float) $item['price'] - $serverPrice) > 0.001) {
                        throw new \Exception("Harga tidak sesuai untuk [{$product->name}]. Silakan refresh menu.");
                    }

                    // Diskon per item divalidasi server: tidak boleh melebihi subtotal item.
                    $itemDiscount = round((float) ($item['discount'] ?? 0), 2);
                    $itemSubtotal = round($item['quantity'] * $serverPrice, 2);
                    if ($itemDiscount < 0 || $itemDiscount > $itemSubtotal) {
                        throw new \Exception("Diskon tidak valid untuk [{$product->name}].");
                    }

                    $itemDiscountTotal += $itemDiscount;
                    $totalPrice += ($itemSubtotal - $itemDiscount);
                }

                // Voucher: dihitung ulang di server, bukan dipercaya dari browser.
                $voucher = null;
                $voucherDiscount = 0;
                if ($request->filled('voucher_code')) {
                    $voucher = Voucher::whereRaw('BINARY code = ?', [$request->voucher_code])->first();
                    if (!$voucher || !$voucher->isCurrentlyValid()) {
                        throw new \Exception('Voucher tidak valid, sudah kedaluwarsa, atau di luar jam promo.');
                    }
                    $voucherDiscount = min((float) $voucher->discount_amount, $totalPrice);
                    $totalPrice -= $voucherDiscount;
                }

                // Penukaran poin loyalty member.
                $customer = null;
                $redeemValue = 0;
                if ($request->filled('customer_id')) {
                    $customer = Customer::lockForUpdate()->findOrFail($request->customer_id);
                    if ((int) $request->input('redeem_points', 0) > 0) {
                        $redeemValue = $customer->redeemPoints((int) $request->redeem_points);
                        $redeemValue = min($redeemValue, $totalPrice);
                        $totalPrice -= $redeemValue;
                    }
                }

                $discountTotal = $itemDiscountTotal + $voucherDiscount + $redeemValue;

                if ($request->payment_amount < $totalPrice) {
                    throw new \Exception('Jumlah uang pembayaran kurang dari total tagihan!');
                }

                $changeAmount = $request->payment_amount - $totalPrice;
                $invoiceNumber = 'INV-' . date('YmdHis') . '-' . rand(100, 999);
                $paymentMethod = $request->input('payment_method', 'cash');

                // 2. Catat Transaksi Penjualan Terhubung ke Shift + Outlet
                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => auth()->id(),
                    'outlet_id' => $shift->outlet_id ?? auth()->user()->outlet_id,
                    'customer_id' => $customer?->id,
                    'shift_id' => $shift->id,
                    'total_price' => $totalPrice,
                    'discount_total' => $discountTotal,
                    'payment_amount' => $request->payment_amount,
                    'change_amount' => $changeAmount,
                    'payment_method' => $paymentMethod,
                    'voucher_code' => $voucher?->code,
                    'idempotency_key' => $idempotencyKey,
                ]);

                // 3. Loop Menu Items & Pengurangan Stok Bahan Baku FIFO dengan LockForUpdate
                foreach ($request->items as $item) {
                    $product = Product::with('recipeItems.material')->findOrFail($item['product_id']);
                    $subtotal = $item['quantity'] * $item['price'];
                    $unitCost = $product->recipeItems->sum(fn ($recipe) => $recipe->quantity * (optional($recipe->material->batches()->latest()->first())->purchase_price ?? 0));

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'subtotal' => $subtotal,
                        'unit_cost' => $unitCost,
                        'cost_total' => $unitCost * $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                        'discount' => round((float) ($item['discount'] ?? 0), 2),
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

                // 4b. Voucher: hitung pemakaian; Loyalty member: accrue poin otomatis.
                $voucher?->increment('used_count');

                if ($customer) {
                    $rate = max(1, (int) Setting::get('loyalty_rate', 1000));
                    $pointsEarned = intdiv((int) floor($totalPrice), $rate);
                    $customer->addLoyaltyPoints($pointsEarned, $sale->id, "Transaksi {$invoiceNumber}");
                }

                // 5. Catat Activity Log Audit
                ActivityLog::record(
                    'POS_SALE',
                    "Transaksi Kasir {$invoiceNumber} oleh " . auth()->user()->name . " Total: Rp " . number_format($totalPrice, 0, ',', '.') . " ({$paymentMethod})"
                    . ($discountTotal > 0 ? " | Diskon: Rp " . number_format($discountTotal, 0, ',', '.') : '')
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
    public function receipt(Request $request, Sale $sale): View
    {
        $sale->load(['user', 'shift.outlet', 'items.product']);

        // Cetak ulang struk hanya untuk kasir outlet yang sama; hitung jumlah reprint utk audit.
        if ($request->user()->can('pos-access')) {
            $sale->increment('print_count');
            ActivityLog::record('POS_RECEIPT_REPRINT', "Cetak ulang struk {$sale->invoice_number} oleh " . $request->user()->name);
        }

        return view('pos.receipt', compact('sale'));
    }
}
