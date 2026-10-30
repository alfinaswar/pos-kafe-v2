<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DokuPaymentService
{
    public function __construct(private readonly string $clientId, private readonly string $secretKey, private readonly string $baseUrl = 'https://api-sandbox.doku.com')
    {
    }

    public function signature(string $requestId, string $timestamp, string $targetPath, array $body): string
    {
        $digest = base64_encode(hash('sha256', json_encode($body, JSON_UNESCAPED_SLASHES), true));
        $component = "Client-Id:{$this->clientId}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$timestamp}\nRequest-Target:{$targetPath}\nDigest:{$digest}";

        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $component, $this->secretKey, true));
    }

    public function createPayment(string $invoice, int $amount, string $returnUrl, string $notifyUrl): array
    {
        $path = '/checkout/v1/payment';
        $requestId = (string) str()->uuid();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $body = [
            'order' => ['amount' => $amount, 'invoice_number' => $invoice],
            'payment' => ['payment_due_date' => 60],
            'customer' => ['name' => 'POS Kafe Customer'],
            'additional_info' => ['return_url' => $returnUrl, 'completed_url' => $returnUrl, 'failed_url' => $returnUrl, 'notify_url' => $notifyUrl],
        ];
        $response = Http::timeout(15)->withHeaders([
            'Client-Id' => $this->clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $this->signature($requestId, $timestamp, $path, $body),
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $path, $body)->throw()->json();

        return ['request_id' => $requestId, 'response' => $response];
    }

    public function verifyNotification(string $requestId, string $timestamp, string $targetPath, array $body, string $providedSignature): bool
    {
        return hash_equals($this->signature($requestId, $timestamp, $targetPath, $body), $providedSignature);
    }
}
