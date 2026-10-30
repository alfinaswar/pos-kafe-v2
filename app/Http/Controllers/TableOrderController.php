<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use App\Models\TableSession;
use App\Models\CustomerOrder;
use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\InventoryLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TableOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pos-access')->only(['index', 'updateStatus']);
    }

    public function index(): View
    {
        $orders = CustomerOrder::with(['session.table', 'items.product'])
            ->whereHas('session.table', fn($query) => $query->where('outlet_id', auth()->user()->outlet_id))
            ->whereIn('status', ['pending', 'accepted', 'preparing', 'ready'])
            ->latest()
            ->paginate(20);

        return view('table-orders.index', compact('orders'));
    }

    public function menu(string $token): View
    {
        $table = CafeTable::where('qr_token', $token)->where('status', 'active')->firstOrFail();
        // dd($table);
        $products = \App\Models\Product::orderBy('name')->get();
        $tableSession = TableSession::firstOrCreate(
            ['cafe_table_id' => $table->id, 'status' => 'open'],
            ['session_token' => bin2hex(random_bytes(24))]
        );

        return view('table-orders.menu', compact('table', 'products', 'tableSession'));
    }

    public function status(string $token): JsonResponse
    {
        $session = TableSession::where('session_token', $token)->where('status', 'open')->firstOrFail();
        return response()->json(['orders' => $session->orders()->latest()->get(['order_number', 'status', 'total', 'created_at'])]);
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.size' => ['nullable', 'in:regular,large'],
            'items.*.modifiers.sugar' => ['nullable', 'in:normal,less,none'],
            'items.*.modifiers.ice' => ['nullable', 'in:normal,less,none'],
            'items.*.modifiers.toppings' => ['nullable', 'array', 'max:5'],
            'items.*.modifiers.toppings.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'destination' => ['nullable', 'in:bar,kitchen'],
        ]);

        $table = CafeTable::where('qr_token', $token)->where('status', 'active')->firstOrFail();

        $order = DB::transaction(function () use ($data, $table): CustomerOrder {
            $session = TableSession::firstOrCreate(
                ['cafe_table_id' => $table->id, 'status' => 'open'],
                ['session_token' => bin2hex(random_bytes(24))]
            );
            $table->update(['occupancy_status' => 'occupied']);

            $order = $session->orders()->create([
                'order_number' => 'QR-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'status' => 'pending',
                'destination' => $data['destination'] ?? 'kitchen',
                'notes' => $data['notes'] ?? null,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = \App\Models\Product::with('bundleItems.product')->findOrFail($item['product_id']);
                $modifierPrices = ['large' => 5000, 'boba' => 3000, 'cheese' => 4000];
                $modifierExtra = collect($item['modifiers']['toppings'] ?? [])->sum(fn($topping) => $modifierPrices[$topping] ?? 0) + (($item['modifiers']['size'] ?? 'regular') === 'large' ? 5000 : 0);
                $unitPrice = $product->price + $modifierExtra;
                $subtotal = $unitPrice * $item['quantity'];
                $unitCost = $product->recipeItems->sum(fn($recipe) => $recipe->quantity * (optional($recipe->material->batches()->latest()->first())->purchase_price ?? 0));
                $unitCost += $product->bundleItems->sum(fn($bundle) => $bundle->product->price * $bundle->quantity);
                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
                foreach ($item['modifiers'] ?? [] as $key => $value) {
                    foreach ((array) $value as $modifierValue) {
                        $orderItem->modifiers()->create(['key' => $key, 'value' => $modifierValue]);
                    }
                }
                $total += $subtotal;
            }

            $order->update(['total' => $total]);
            return $order;
        });

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'status' => $order->status,
        ], 201);
    }

    public function updateStatus(Request $request, CustomerOrder $order): JsonResponse
    {
        abort_unless($order->session->table->outlet_id === auth()->user()->outlet_id, 403);
        $data = $request->validate([
            'status' => ['required', 'in:pending,accepted,preparing,ready,completed,cancelled'],
        ]);

        $order->update(['status' => $data['status']]);

        return response()->json(['success' => true, 'status' => $order->status]);
    }

    public function checkout(Request $request, CustomerOrder $order): JsonResponse
    {
        abort_unless($order->session->table->outlet_id === auth()->user()->outlet_id, 403);
        $data = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:30'],
        ]);

        if (!in_array($order->status, ['accepted', 'ready'], true)) {
            return response()->json(['message' => 'Order belum dapat dibayar.'], 422);
        }

        $sale = DB::transaction(function () use ($data, $order): Sale {
            $shift = CashierShift::where('user_id', auth()->id())
                ->where('status', 'open')
                ->lockForUpdate()
                ->firstOrFail();

            if ($data['payment_amount'] < $order->total) {
                abort(422, 'Jumlah pembayaran kurang dari total order.');
            }

            $paymentMethod = $data['payment_method'];
            $sale = Sale::create([
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'user_id' => auth()->id(),
                'shift_id' => $shift->id,
                'total_price' => $order->total,
                'payment_amount' => $data['payment_amount'],
                'change_amount' => $data['payment_amount'] - $order->total,
                'payment_method' => $paymentMethod,
            ]);

            foreach ($order->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);

                foreach ($item->product->recipeItems as $recipe) {
                    $material = Material::whereKey($recipe->material_id)->lockForUpdate()->firstOrFail();
                    $neededQty = $recipe->quantity * $item->quantity;
                    if ($material->stock < $neededQty) {
                        abort(422, "Stok bahan baku [{$material->name}] tidak mencukupi.");
                    }

                    $remaining = $neededQty;
                    $batches = MaterialBatch::where('material_id', $material->id)
                        ->where('remaining_qty', '>', 0)
                        ->orderBy('created_at')
                        ->lockForUpdate()
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remaining <= 0)
                            break;
                        $take = min($batch->remaining_qty, $remaining);
                        $batch->decrement('remaining_qty', $take);
                        $remaining -= $take;
                        InventoryLog::create([
                            'material_id' => $material->id,
                            'batch_id' => $batch->id,
                            'type' => 'out',
                            'quantity' => $take,
                            'stock_after' => $material->stock - ($neededQty - $remaining),
                            'reference' => 'QR Table Order #' . $order->order_number,
                        ]);
                    }

                    $material->decrement('stock', $neededQty);
                    if ($remaining > 0) {
                        InventoryLog::create([
                            'material_id' => $material->id,
                            'type' => 'out',
                            'quantity' => $remaining,
                            'stock_after' => $material->fresh()->stock,
                            'reference' => 'QR Table Order #' . $order->order_number . ' - Unbatched',
                        ]);
                    }
                }
            }

            $column = $paymentMethod === 'cash' ? 'total_sales_cash' : 'total_sales_non_cash';
            $shift->increment($column, $order->total);
            $shift->refresh();
            $shift->expected_cash = $shift->calculateExpectedCash();
            $shift->save();
            $order->update(['status' => 'completed']);
            if (!$order->session->orders()->whereIn('status', ['pending', 'accepted', 'preparing', 'ready'])->where('id', '!=', $order->id)->exists()) {
                $order->session->update(['status' => 'closed', 'closed_at' => now()]);
                $order->session->table()->update(['occupancy_status' => 'available']);
            }

            return $sale;
        });

        return response()->json(['success' => true, 'sale_id' => $sale->id, 'invoice' => $sale->invoice_number], 201);
    }

    public function splitCheckout(Request $request, CustomerOrder $order): JsonResponse
    {
        abort_unless($order->session->table->outlet_id === auth()->user()->outlet_id, 403);
        $data = $request->validate(['payments' => ['required', 'array', 'min:2'], 'payments.*.amount' => ['required', 'numeric', 'gt:0'], 'payments.*.payment_method' => ['required', 'string', 'max:30']]);
        abort_unless($order->status === 'accepted' || $order->status === 'ready', 422, 'Order belum dapat dibayar.');
        abort_unless(round(collect($data['payments'])->sum('amount'), 2) === round((float) $order->total, 2), 422, 'Total pembayaran tidak sama.');
        $sales = DB::transaction(function () use ($data, $order): array {
            $shift = CashierShift::where('user_id', auth()->id())->where('status', 'open')->lockForUpdate()->firstOrFail();
            $sales = [];
            foreach ($data['payments'] as $index => $payment) {
                $sale = Sale::create(['invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . random_int(100, 999) . '-' . $index, 'user_id' => auth()->id(), 'shift_id' => $shift->id, 'total_price' => $payment['amount'], 'payment_amount' => $payment['amount'], 'change_amount' => 0, 'payment_method' => $payment['payment_method']]);
                $sales[] = $sale;
                $shift->increment($payment['payment_method'] === 'cash' ? 'total_sales_cash' : 'total_sales_non_cash', $payment['amount']);
            }
            $order->update(['status' => 'completed']);
            return $sales;
        });
        return response()->json(['success' => true, 'sale_ids' => collect($sales)->pluck('id')], 201);
    }
}
