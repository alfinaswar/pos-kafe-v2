<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\DokuPayment;
use App\Models\PaymentSetting;
use App\Services\DokuPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\CashierShift;

class DokuPaymentController extends Controller
{
    public function create(Request $request, CustomerOrder $order): JsonResponse
    {
        abort_unless($order->status !== 'completed', 422, 'Order sudah selesai.');
        $settings = PaymentSetting::whereIn('key', ['doku_client_id', 'doku_secret_key', 'doku_mode', 'doku_enabled'])->pluck('value', 'key');
        abort_unless(($settings['doku_enabled'] ?? '0') === '1', 422, 'DOKU belum diaktifkan.');

        $invoice = 'DOKU-' . $order->order_number;
        $payment = DokuPayment::firstOrCreate(
            ['customer_order_id' => $order->id],
            ['invoice_number' => $invoice, 'request_id' => (string) str()->uuid(), 'status' => 'pending', 'amount' => $order->total]
        );
        if ($payment->payment_url) return response()->json(['payment_url' => $payment->payment_url, 'status' => $payment->status]);

        $baseUrl = ($settings['doku_mode'] ?? 'sandbox') === 'production' ? 'https://api.doku.com' : 'https://api-sandbox.doku.com';
        $result = (new DokuPaymentService($settings['doku_client_id'], $settings['doku_secret_key'], $baseUrl))->createPayment($invoice, (int) $order->total, route('doku.payment.return'), route('doku.payment.notify'));
        $provider = $result['response'];
        $url = data_get($provider, 'response.payment.url') ?? data_get($provider, 'payment.url');
        abort_unless($url, 502, 'DOKU tidak mengembalikan URL pembayaran.');
        $payment->update(['request_id' => $result['request_id'], 'payment_url' => $url, 'provider_response' => $provider]);

        return response()->json(['payment_url' => $url, 'status' => $payment->status]);
    }

    public function notify(Request $request)
    {
        $settings = PaymentSetting::whereIn('key', ['doku_client_id', 'doku_secret_key'])->get()->mapWithKeys(fn ($setting) => [$setting->key => $setting->value]);
        $body = $request->json()->all();
        $requestId = (string) $request->header('Request-Id');
        $timestamp = (string) $request->header('Request-Timestamp');
        $signature = (string) $request->header('Signature');
        $service = new DokuPaymentService($settings['doku_client_id'] ?? '', $settings['doku_secret_key'] ?? '');
        abort_unless($requestId && $timestamp && $signature && $service->verifyNotification($requestId, $timestamp, '/payments/doku/notify', $body, $signature), 401, 'Invalid DOKU signature.');

        $invoice = data_get($body, 'order.invoice_number') ?? data_get($body, 'order.invoiceNumber');
        $status = strtoupper((string) (data_get($body, 'transaction.status') ?? data_get($body, 'transaction.status_code')));
        $payment = DokuPayment::where('invoice_number', $invoice)->firstOrFail();
        DB::transaction(function () use ($payment, $status, $body): void {
            if ($payment->status === 'paid') return;
            $newStatus = in_array($status, ['SUCCESS', 'PAID'], true) ? 'paid' : strtolower($status);
            $payment->update(['status' => $newStatus, 'provider_response' => $body]);
            if ($newStatus !== 'paid') return;
            $order = $payment->order()->with('items')->lockForUpdate()->firstOrFail();
            if ($order->status === 'completed') return;
            $shift = CashierShift::where('outlet_id', $order->session->table->outlet_id)->where('status', 'open')->latest()->lockForUpdate()->firstOrFail();
            $sale = Sale::create(['invoice_number' => $payment->invoice_number, 'user_id' => $shift->user_id, 'shift_id' => $shift->id, 'total_price' => $order->total, 'payment_amount' => $order->total, 'change_amount' => 0, 'payment_method' => 'doku']);
            foreach ($order->items as $item) SaleItem::create(['sale_id' => $sale->id, 'product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'subtotal' => $item->subtotal]);
            $shift->increment('total_sales_non_cash', $order->total);
            $order->update(['status' => 'completed']);
        });

        return response()->json(['received' => true]);
    }

    public function return()
    {
        return redirect('/');
    }
}
