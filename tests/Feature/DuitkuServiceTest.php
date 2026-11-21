<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\Order;
use App\Services\Payment\DuitkuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DuitkuServiceTest extends TestCase
{
    use RefreshDatabase;

    private const SANDBOX_URL = 'https://sandbox.duitku.com/webapi/api/merchant';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.duitku.merchant_code' => 'D1234',
            'services.duitku.api_key' => 'duitku-key',
            'services.duitku.sandbox' => true,
        ]);
    }

    public function test_base_url_follows_sandbox_flag(): void
    {
        $this->assertSame(self::SANDBOX_URL, app(DuitkuService::class)->baseUrl());

        config(['services.duitku.sandbox' => false]);

        $this->assertSame('https://passport.duitku.com/webapi/api/merchant', app(DuitkuService::class)->baseUrl());
    }

    public function test_create_transaction_sends_server_side_total_with_hmac_signature_and_logs_without_signature(): void
    {
        Http::fake([
            self::SANDBOX_URL.'/v2/inquiry' => Http::response([
                'merchantCode' => 'D1234',
                'reference' => 'D1234REF',
                'paymentUrl' => 'https://sandbox.duitku.com/topup/pay',
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
            ]),
        ]);

        $order = Order::factory()->create([
            'invoice' => 'INV1',
            'product_name' => '86 Diamonds',
            'price' => 20000,
            'total' => 20000,
            'expired_at' => now()->addMinutes(15),
        ]);

        $response = app(DuitkuService::class)->createTransaction($order, 'BK');

        $this->assertSame('D1234REF', $response->json('reference'));
        Http::assertSent(fn (Request $request): bool => $request->url() === self::SANDBOX_URL.'/v2/inquiry'
            && $request['merchantCode'] === 'D1234'
            && $request['merchantOrderId'] === 'INV1'
            && $request['paymentAmount'] === 20000
            && $request['paymentMethod'] === 'BK'
            && $request['signature'] === hash_hmac('sha256', 'D1234'.'INV1'.'20000', 'duitku-key')
            && $request['itemDetails'] === [['name' => '86 Diamonds', 'price' => 20000, 'quantity' => 1]]
            && $request['callbackUrl'] === route('webhook.duitku')
            && $request['returnUrl'] === route('invoice.show', $order)
            && $request['expiryPeriod'] >= 14 && $request['expiryPeriod'] <= 15);

        $log = ApiLog::where('provider', 'duitku')->sole();
        $this->assertSame('INV1', $log->reference);
        $this->assertSame('outgoing', $log->direction);
        $this->assertArrayNotHasKey('signature', $log->request);
    }

    public function test_store_payment_details_saves_qr_string_as_payment_code(): void
    {
        Http::fake([
            self::SANDBOX_URL.'/v2/inquiry' => Http::response([
                'reference' => 'D1234REF',
                'paymentUrl' => 'https://sandbox.duitku.com/topup/pay',
                'qrString' => '00020101021226610016ID.CO.SHOPEE.WWW',
                'statusCode' => '00',
            ]),
        ]);
        $order = Order::factory()->create(['payment_method' => 'SP']);
        $duitku = app(DuitkuService::class);

        $this->assertTrue($duitku->storePaymentDetails($order, $duitku->createTransaction($order, 'SP')));

        $order->refresh();
        $this->assertSame('D1234REF', $order->payment_reference);
        $this->assertSame('https://sandbox.duitku.com/topup/pay', $order->payment_url);
        $this->assertSame('00020101021226610016ID.CO.SHOPEE.WWW', $order->payment_code);
    }

    public function test_store_payment_details_ignores_rejected_transaction(): void
    {
        Http::fake([self::SANDBOX_URL.'/v2/inquiry' => Http::response(['Message' => 'Invalid Signature.'], 401)]);
        $order = Order::factory()->create();
        $duitku = app(DuitkuService::class);

        $this->assertFalse($duitku->storePaymentDetails($order, $duitku->createTransaction($order, 'SP')));
        $this->assertNull($order->refresh()->payment_code);
    }

    public function test_payment_methods_uses_hmac_signature_over_amount_and_datetime(): void
    {
        Http::fake([
            self::SANDBOX_URL.'/paymentmethod/getpaymentmethod' => Http::response(['paymentFee' => [['paymentMethod' => 'BK']]]),
        ]);

        $response = app(DuitkuService::class)->paymentMethods(20000);

        $this->assertSame('BK', $response->json('paymentFee.0.paymentMethod'));
        Http::assertSent(fn (Request $request): bool => $request['merchantcode'] === 'D1234'
            && $request['amount'] === 20000
            && $request['signature'] === hash_hmac('sha256', 'D1234'.'20000'.$request['datetime'], 'duitku-key'));
    }

    public function test_transaction_status_posts_json_with_hmac_signature(): void
    {
        Http::fake([
            self::SANDBOX_URL.'/transactionStatus' => Http::response(['statusCode' => '01', 'reference' => 'D1234REF']),
        ]);

        $response = app(DuitkuService::class)->transactionStatus('INV1');

        $this->assertSame('01', $response->json('statusCode'));
        Http::assertSent(fn (Request $request): bool => $request->isJson()
            && $request['merchantOrderId'] === 'INV1'
            && $request['signature'] === hash_hmac('sha256', 'D1234'.'INV1', 'duitku-key'));
    }

    public function test_callback_signature_is_case_insensitive_hex_hmac_sha256(): void
    {
        $payload = [
            'merchantCode' => 'D1234',
            'amount' => '20000',
            'merchantOrderId' => 'INV1',
            'signature' => strtoupper(hash_hmac('sha256', 'D1234'.'20000'.'INV1', 'duitku-key')),
        ];

        $this->assertTrue(app(DuitkuService::class)->isValidCallbackSignature($payload));
    }
}
