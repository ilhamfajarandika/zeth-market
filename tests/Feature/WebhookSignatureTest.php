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
            'services.melo.webhook_secret' => 'melo-secret',
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

    public function test_melo_webhook_with_valid_signature_is_accepted(): void
    {
        $body = json_encode(['buyer_trx_id' => 'INV456', 'status' => 'success']);

        $response = $this->call('POST', route('webhook.melo'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_H2H_SIGNATURE' => hash_hmac('sha256', $body, 'melo-secret'),
        ], $body);

        $response->assertOk();
        $this->assertTrue(ApiLog::where('reference', 'INV456')->sole()->signature_valid);
    }

    public function test_melo_webhook_with_wrong_signature_is_rejected_and_logged(): void
    {
        $body = json_encode(['buyer_trx_id' => 'INV456', 'status' => 'success']);

        $response = $this->call('POST', route('webhook.melo'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_H2H_SIGNATURE' => hash_hmac('sha256', $body, 'other-secret'),
        ], $body);

        $response->assertUnauthorized();
        $this->assertFalse(ApiLog::where('reference', 'INV456')->sole()->signature_valid);
    }

    public function test_melo_webhook_without_signature_is_rejected(): void
    {
        $response = $this->postJson(route('webhook.melo'), ['buyer_trx_id' => 'INV456']);

        $response->assertUnauthorized();
    }

    public function test_melo_webhook_is_rejected_when_webhook_secret_is_not_configured(): void
    {
        config(['services.melo.webhook_secret' => null]);

        $body = json_encode(['buyer_trx_id' => 'INV456']);

        $response = $this->call('POST', route('webhook.melo'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_H2H_SIGNATURE' => hash_hmac('sha256', $body, ''),
        ], $body);

        $response->assertUnauthorized();
    }
}
