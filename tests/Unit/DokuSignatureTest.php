<?php

namespace Tests\Unit;

use App\Services\DokuPaymentService;
use PHPUnit\Framework\TestCase;

class DokuSignatureTest extends TestCase
{
    public function test_signature_matches_doku_hmac_sha256_contract(): void
    {
        $service = new DokuPaymentService('MCH-TEST', 'secret-test');

        $signature = $service->signature(
            'REQ-123',
            '2020-10-21T03:38:28Z',
            '/checkout/v1/payment',
            ['order' => ['amount' => 15000, 'invoice_number' => 'INV-001']]
        );

        $this->assertSame('HMACSHA256=fC2bmC1810GrIXj5TpFxfotBNtQu6FJWS9s56h8z2wI=', $signature);
    }

    public function test_webhook_signature_verification_accepts_only_matching_headers(): void
    {
        $service = new DokuPaymentService('MCH-TEST', 'secret-test');
        $body = ['order' => ['invoice_number' => 'DOKU-QR-001'], 'transaction' => ['status' => 'SUCCESS']];
        $signature = $service->signature('REQ-WEBHOOK', '2020-10-21T03:38:28Z', '/payments/doku/notify', $body);

        $this->assertTrue($service->verifyNotification('REQ-WEBHOOK', '2020-10-21T03:38:28Z', '/payments/doku/notify', $body, $signature));
        $this->assertFalse($service->verifyNotification('REQ-WEBHOOK', '2020-10-21T03:38:28Z', '/payments/doku/notify', $body, 'HMACSHA256=invalid'));
    }
}
