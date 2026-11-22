<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Game;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SANDBOX_URL = 'https://sandbox.duitku.com/webapi/api/merchant';

    private Game $game;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.duitku.merchant_code' => 'D1234',
            'services.duitku.api_key' => 'duitku-key',
            'services.duitku.sandbox' => true,
        ]);

        $this->game = Game::factory()->create();
        $this->product = Product::factory()->for($this->game)->create(['name' => '70 Diamonds', 'price' => 9000]);
    }

    /**
     * @param  array<string, mixed>  $inquiry
     */
    private function fakeDuitku(array $inquiry = []): void
    {
        Http::fake([
            self::SANDBOX_URL.'/paymentmethod/getpaymentmethod' => Http::response([
                'responseCode' => '00',
                'paymentFee' => [
                    ['paymentMethod' => 'SP', 'paymentName' => 'ShopeePay QRIS', 'paymentImage' => 'https://img.test/sp.png', 'totalFee' => '630.00'],
                    ['paymentMethod' => 'BC', 'paymentName' => 'BCA VA', 'paymentImage' => 'https://img.test/bc.png', 'totalFee' => '5000'],
                ],
            ]),
            self::SANDBOX_URL.'/v2/inquiry' => Http::response($inquiry + [
                'reference' => 'D1234REF',
                'paymentUrl' => 'https://sandbox.duitku.com/pay',
                'qrString' => '000201QRDATA',
                'statusCode' => '00',
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'product_id' => $this->product->id,
            'target_user_id' => '123456789',
            'whatsapp' => '081234567890',
            'email' => '',
            'payment_method' => 'SP',
            'id_confirmed' => true,
        ];
    }

    public function test_checkout_creates_pending_order_with_server_side_fee_and_redirects_to_invoice(): void
    {
        $this->fakeDuitku();

        $response = $this->post(route('checkout.store', $this->game), $this->payload([
            'price' => 1,
            'fee' => 0,
            'total' => 1,
        ]));

        $order = Order::sole();
        $response->assertRedirect(route('invoice.show', $order));
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('70 Diamonds', $order->product_name);
        $this->assertSame(9000, $order->price);
        $this->assertSame(630, $order->fee);
        $this->assertSame(9630, $order->total);
        $this->assertSame('SP', $order->payment_method);
        $this->assertSame('123456789', $order->target_user_id);
        $this->assertSame('081234567890', $order->whatsapp);
        $this->assertSame('000201QRDATA', $order->payment_code);
        $this->assertEqualsWithDelta(now()->addMinutes(15)->timestamp, $order->expired_at->timestamp, 5);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/inquiry')
            && $request['paymentAmount'] === 9630
            && $request['paymentMethod'] === 'SP'
            && $request['merchantOrderId'] === $order->invoice);
    }

    public function test_checkout_requires_zone_when_game_needs_it(): void
    {
        $this->fakeDuitku();
        $game = Game::factory()->withZone()->create();
        $product = Product::factory()->for($game)->create();

        $this->post(route('checkout.store', $game), $this->payload(['product_id' => $product->id]))
            ->assertSessionHasErrors('target_zone_id');

        $this->post(route('checkout.store', $game), $this->payload(['product_id' => $product->id, 'target_zone_id' => '12']))
            ->assertSessionHasErrors('target_zone_id');

        $this->post(route('checkout.store', $game), $this->payload(['product_id' => $product->id, 'target_zone_id' => '1234']))
            ->assertSessionHasNoErrors();

        $this->assertSame('1234', Order::sole()->target_zone_id);
    }

    public function test_checkout_validates_input(): void
    {
        $this->fakeDuitku();

        $cases = [
            'target_user_id' => ['target_user_id' => 'abc'],
            'whatsapp' => ['whatsapp' => ''],
            'id_confirmed' => ['id_confirmed' => false],
            'email' => ['email' => 'bukan-email'],
            'payment_method' => ['payment_method' => ''],
        ];

        foreach ($cases as $field => $override) {
            $this->post(route('checkout.store', $this->game), $this->payload($override))
                ->assertSessionHasErrors($field);
        }

        $this->post(route('checkout.store', $this->game), $this->payload(['whatsapp' => '12345']))
            ->assertSessionHasErrors('whatsapp');

        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_checkout_rejects_products_that_are_not_sellable_here(): void
    {
        $this->fakeDuitku();
        $products = [
            Product::factory()->create(),
            Product::factory()->for($this->game)->create(['is_active' => false]),
        ];

        foreach ($products as $product) {
            $this->post(route('checkout.store', $this->game), $this->payload(['product_id' => $product->id]))
                ->assertSessionHasErrors('product_id');
        }

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_rejects_payment_method_not_offered_by_duitku(): void
    {
        $this->fakeDuitku();

        $this->post(route('checkout.store', $this->game), $this->payload(['payment_method' => 'XX']))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_failed_inquiry_marks_order_failed_and_returns_error(): void
    {
        $this->fakeDuitku(['statusCode' => '01']);

        $this->from(route('game.show', $this->game))
            ->post(route('checkout.store', $this->game), $this->payload())
            ->assertRedirect(route('game.show', $this->game))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(OrderStatus::Failed, Order::sole()->status);
    }

    public function test_checkout_for_inactive_game_is_not_found(): void
    {
        $this->fakeDuitku();
        $this->game->update(['is_active' => false]);

        $this->post(route('checkout.store', $this->game), $this->payload())->assertNotFound();

        $this->assertSame(0, Order::count());
    }
}
