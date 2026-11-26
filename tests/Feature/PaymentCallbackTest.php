<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SendTopupToMelo;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.duitku.merchant_code' => 'D1234',
            'services.duitku.api_key' => 'duitku-key',
        ]);

        Bus::fake([SendTopupToMelo::class]);
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Kirim callback Duitku bertanda tangan sah (field mengikuti duitku-collection.json).
     */
    private function sendCallback(Order $order, string $resultCode = '00', ?int $amount = null): TestResponse
    {
        $amount = (string) ($amount ?? $order->total);

        return $this->post(route('webhook.duitku'), [
            'merchantCode' => 'D1234',
            'amount' => $amount,
            'merchantOrderId' => $order->invoice,
            'productDetail' => $order->product_name,
            'additionalParam' => '',
            'paymentMethod' => 'BK',
            'resultCode' => $resultCode,
            'merchantUserId' => 'buyer@example.test',
            'reference' => 'DREF123',
            'signature' => hash_hmac('sha256', 'D1234'.$amount.$order->invoice, 'duitku-key'),
        ]);
    }

    public function test_paid_callback_marks_order_paid_and_sends_topup_once(): void
    {
        $order = Order::factory()->create();

        $this->sendCallback($order)->assertOk();
        $this->sendCallback($order)->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('DREF123', $order->payment_reference);
        Bus::assertDispatchedTimes(SendTopupToMelo::class, 1);
    }

    public function test_failed_callback_marks_order_failed(): void
    {
        $order = Order::factory()->create();

        $this->sendCallback($order, '02')->assertOk();

        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
        Bus::assertNotDispatched(SendTopupToMelo::class);
    }

    public function test_redirect_only_process_code_keeps_order_pending(): void
    {
        $order = Order::factory()->create();

        $this->sendCallback($order, '01')->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Bus::assertNotDispatched(SendTopupToMelo::class);
    }

    public function test_amount_mismatch_keeps_order_pending_and_alerts_admin(): void
    {
        $order = Order::factory()->create(['total' => 20000]);

        $this->sendCallback($order, amount: 1000)->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame('Nominal pembayaran tidak cocok', $this->admin->notifications()->sole()->data['title']);
        Bus::assertNotDispatched(SendTopupToMelo::class);
    }

    public function test_payment_after_expiry_keeps_order_expired_and_alerts_admin(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Expired]);

        $this->sendCallback($order)->assertOk();

        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
        $this->assertSame('Pembayaran masuk untuk order yang sudah ditutup', $this->admin->notifications()->sole()->data['title']);
        Bus::assertNotDispatched(SendTopupToMelo::class);
    }
}
