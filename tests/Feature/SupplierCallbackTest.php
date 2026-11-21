<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SendTopupToMelo;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SupplierCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://melo.test/api/v1/h2h';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.melo.base_url' => self::BASE_URL,
            'services.melo.api_key' => 'test-api-key',
            'services.melo.secret_key' => 'test-secret-key',
            'services.melo.webhook_secret' => 'melo-secret',
            'services.melo.sandbox' => true,
        ]);

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Kirim webhook Melo bertanda tangan sah (bentuk payload mengikuti melostore-h2h.json).
     *
     * @param  array<string, mixed>  $payload
     */
    private function webhook(array $payload): TestResponse
    {
        $body = json_encode($payload);

        return $this->call('POST', route('webhook.melo'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_H2H_SIGNATURE' => hash_hmac('sha256', $body, 'melo-secret'),
        ], $body);
    }

    private function processingOrder(): Order
    {
        return Order::factory()->paid()->create(['status' => OrderStatus::Processing, 'supplier_reference' => 'trx-1']);
    }

    // --- Webhook Melo ------------------------------------------------------

    public function test_success_webhook_completes_order_with_serial_number(): void
    {
        $order = $this->processingOrder();

        $this->webhook(['id' => 'trx-1', 'buyer_trx_id' => $order->invoice, 'status' => 'success', 'serial_number' => 'SN-001'])->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame('SN-001', $order->serial_number);
        $this->assertSame('success', $order->supplier_status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_failed_webhook_fails_order_and_alerts_admin(): void
    {
        $order = $this->processingOrder();

        $this->webhook(['buyer_trx_id' => $order->invoice, 'status' => 'failed', 'error_code' => 'SUPPLIER_ERROR', 'message' => 'Supplier error.'])->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertSame('Supplier error.', $order->supplier_message);
        $this->assertSame('Top up gagal setelah dibayar', $this->admin->notifications()->sole()->data['title']);
    }

    public function test_action_change_event_does_not_touch_order(): void
    {
        $order = $this->processingOrder();

        $this->webhook(['event' => 'transaction.action_change', 'event_id' => 'evt-1', 'buyer_trx_id' => $order->invoice, 'status' => 'success'])->assertOk();

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
    }

    public function test_webhook_for_final_order_is_ignored(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Success, 'serial_number' => 'SN-OLD']);

        $this->webhook(['buyer_trx_id' => $order->invoice, 'status' => 'failed'])->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame('SN-OLD', $order->serial_number);
    }

    public function test_refunded_status_keeps_order_and_alerts_admin(): void
    {
        $order = $this->processingOrder();

        $this->webhook(['buyer_trx_id' => $order->invoice, 'status' => 'refunded'])->assertOk();

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertSame('Transaksi Melo di-refund', $this->admin->notifications()->sole()->data['title']);
    }

    // --- Job kirim ke Melo -------------------------------------------------

    public function test_accepted_topup_moves_order_to_processing(): void
    {
        Http::fake([self::BASE_URL.'/transaction' => Http::response(['data' => ['id' => 'trx-9', 'status' => 'pending']], 201)]);
        $order = Order::factory()->paid()->create();

        SendTopupToMelo::dispatchSync($order);

        $order->refresh();
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame('trx-9', $order->supplier_reference);
        $this->assertSame('pending', $order->supplier_status);
    }

    public function test_topup_that_is_already_final_completes_order(): void
    {
        Http::fake([self::BASE_URL.'/transaction' => Http::response(['data' => ['id' => 'trx-9', 'status' => 'success', 'serial_number' => 'SN-9']], 201)]);
        $order = Order::factory()->paid()->create();

        SendTopupToMelo::dispatchSync($order);

        $order->refresh();
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame('SN-9', $order->serial_number);
    }

    public function test_server_error_checks_status_before_resending(): void
    {
        $order = Order::factory()->paid()->create();
        Http::fake([
            self::BASE_URL.'/transaction' => Http::response(['message' => 'error'], 500),
            self::BASE_URL.'/transaction/'.$order->invoice => Http::response(['data' => ['id' => 'trx-9', 'status' => 'processing']]),
        ]);

        SendTopupToMelo::dispatchSync($order);

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && str_ends_with($request->url(), '/transaction/'.$order->invoice));
    }

    public function test_rejected_topup_keeps_order_paid_and_alerts_admin(): void
    {
        Http::fake([self::BASE_URL.'/transaction' => Http::response(['message' => 'Invalid target'], 422)]);
        $order = Order::factory()->paid()->create();

        SendTopupToMelo::dispatchSync($order);

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('Invalid target', $order->supplier_message);
        $this->assertSame('Pengiriman top up ke Melo bermasalah', $this->admin->notifications()->sole()->data['title']);
    }

    public function test_job_skips_order_that_is_not_paid(): void
    {
        Http::fake();
        $order = Order::factory()->create();

        SendTopupToMelo::dispatchSync($order);

        Http::assertNothingSent();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }
}
