<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tripay.private_key' => 'tripay-secret',
            'services.digiflazz.webhook_secret' => 'digiflazz-secret',
        ]);
    }

    public function test_tripay_callback_with_valid_signature_is_accepted_and_logged(): void
    {
        $body = json_encode(['merchant_ref' => 'INV123', 'status' => 'PAID']);

        $response = $this->call('POST', route('webhook.tripay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_SIGNATURE' => hash_hmac('sha256', $body, 'tripay-secret'),
        ], $body);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(ApiLog::where('reference', 'INV123')->sole()->signature_valid);
    }

    public function test_tripay_callback_with_invalid_signature_is_rejected(): void
    {
        $body = json_encode(['merchant_ref' => 'INV123', 'status' => 'PAID']);

        $response = $this->call('POST', route('webhook.tripay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_SIGNATURE' => 'forged',
        ], $body);

        $response->assertForbidden();
        $this->assertFalse(ApiLog::where('reference', 'INV123')->sole()->signature_valid);
    }

    public function test_digiflazz_webhook_with_valid_signature_is_accepted(): void
    {
        $body = json_encode(['data' => ['ref_id' => 'INV456', 'status' => 'Sukses']]);

        $response = $this->call('POST', route('webhook.digiflazz'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', $body, 'digiflazz-secret'),
        ], $body);

        $response->assertOk();
        $this->assertTrue(ApiLog::where('reference', 'INV456')->sole()->signature_valid);
    }

    public function test_digiflazz_webhook_without_signature_is_rejected(): void
    {
        $response = $this->postJson(route('webhook.digiflazz'), ['data' => ['ref_id' => 'INV456']]);

        $response->assertForbidden();
    }
}
