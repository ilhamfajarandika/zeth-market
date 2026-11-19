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
            'services.duitku.merchant_code' => 'D1234',
            'services.duitku.api_key' => 'duitku-key',
            'services.melo.webhook_secret' => 'melo-secret',
        ]);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function duitkuPayload(array $overrides = []): array
    {
        $payload = array_merge([
            'merchantCode' => 'D1234',
            'amount' => '20000',
            'merchantOrderId' => 'INV123',
            'productDetail' => 'Diamond',
            'additionalParam' => '',
            'paymentCode' => 'BK',
            'resultCode' => '00',
            'merchantUserId' => 'buyer@example.test',
            'reference' => 'D1234REF',
        ], $overrides);

        $payload['signature'] ??= hash_hmac('sha256', $payload['merchantCode'].$payload['amount'].$payload['merchantOrderId'], 'duitku-key');

        return $payload;
    }

    public function test_duitku_callback_with_valid_signature_is_accepted_and_logged(): void
    {
        $payload = $this->duitkuPayload(['signature' => hash_hmac('sha256', 'D1234'.'20000'.'INV123', 'duitku-key')]);

        $response = $this->post(route('webhook.duitku'), $payload);

        $response->assertOk()->assertJson(['success' => true]);
        $log = ApiLog::where('reference', 'INV123')->sole();
        $this->assertTrue($log->signature_valid);
        $this->assertArrayNotHasKey('signature', $log->request);
    }

    public function test_duitku_callback_with_invalid_signature_is_rejected_and_logged(): void
    {
        $response = $this->post(route('webhook.duitku'), $this->duitkuPayload(['signature' => 'forged']));

        $response->assertForbidden();
        $this->assertFalse(ApiLog::where('reference', 'INV123')->sole()->signature_valid);
    }

    public function test_duitku_callback_without_signature_is_rejected(): void
    {
        $payload = $this->duitkuPayload(['signature' => '']);

        $this->post(route('webhook.duitku'), $payload)->assertForbidden();
    }

    public function test_duitku_callback_with_tampered_amount_is_rejected(): void
    {
        $payload = $this->duitkuPayload(['signature' => hash_hmac('sha256', 'D1234'.'20000'.'INV123', 'duitku-key')]);
        $payload['amount'] = '1000';

        $this->post(route('webhook.duitku'), $payload)->assertForbidden();
    }

    public function test_duitku_callback_for_other_merchant_is_rejected(): void
    {
        $payload = $this->duitkuPayload(['merchantCode' => 'D9999', 'signature' => hash_hmac('sha256', 'D9999'.'20000'.'INV123', 'duitku-key')]);

        $this->post(route('webhook.duitku'), $payload)->assertForbidden();
    }

    public function test_duitku_callback_is_rejected_when_api_key_is_not_configured(): void
    {
        config(['services.duitku.api_key' => null]);

        $payload = $this->duitkuPayload(['signature' => hash_hmac('sha256', 'D1234'.'20000'.'INV123', '')]);

        $this->post(route('webhook.duitku'), $payload)->assertForbidden();
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
