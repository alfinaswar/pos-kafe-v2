<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use App\Models\TableSession;
use App\Models\CustomerOrder;
use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\SaleItem;
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
            ->whereIn('status', ['pending', 'accepted', 'preparing', 'ready'])
            ->latest()
            ->paginate(20);

        return view('table-orders.index', compact('orders'));
    }

    public function menu(string $token): View
    {
        $table = CafeTable::where('qr_token', $token)->where('status', 'active')->firstOrFail();
        $products = \App\Models\Product::orderBy('name')->get();

        return view('table-orders.menu', compact('table', 'products'));
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $table = CafeTable::where('qr_token', $token)->where('status', 'active')->firstOrFail();

        $order = DB::transaction(function () use ($data, $table): CustomerOrder {
            $session = TableSession::firstOrCreate(
                ['cafe_table_id' => $table->id, 'status' => 'open'],
                ['session_token' => bin2hex(random_bytes(24))]
            );

            $order = $session->orders()->create([
                'order_number' => 'QR-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = \App\Models\Product::findOrFail($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
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
        $data = $request->validate([
            'status' => ['required', 'in:pending,accepted,preparing,ready,completed,cancelled'],
        ]);

        $order->update(['status' => $data['status']]);

        return response()->json(['success' => true, 'status' => $order->status]);
    }

    public function checkout(Request $request, CustomerOrder $order): JsonResponse
    {
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
            }

            $column = $paymentMethod === 'cash' ? 'total_sales_cash' : 'total_sales_non_cash';
            $shift->increment($column, $order->total);
            $shift->refresh();
            $shift->expected_cash = $shift->calculateExpectedCash();
            $shift->save();
            $order->update(['status' => 'completed']);

            return $sale;
        });

        return response()->json(['success' => true, 'sale_id' => $sale->id, 'invoice' => $sale->invoice_number], 201);
    }
}
