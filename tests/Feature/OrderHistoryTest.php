<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
    }

    public function test_guest_is_redirected_to_login_from_order_history(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('login'));
    }

    public function test_customer_sees_only_their_own_orders_with_explicit_props(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $ownOrder = Order::factory()->for($customer)->create([
            'status' => OrderStatus::Success,
            'product_name' => '86 Diamonds',
            'supplier_reference' => 'SECRET-SUPPLIER-REF',
            'supplier_message' => 'Internal debug note',
        ]);

        Order::factory()->for($otherCustomer)->create(['product_name' => 'Order Orang Lain']);
        Order::factory()->create(['user_id' => null, 'product_name' => 'Order Guest']);

        $response = $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Index')
                ->where('orders.total', 1)
                ->has('orders.data', 1)
                ->where('orders.data.0.invoice', $ownOrder->invoice)
                ->where('orders.data.0.productName', '86 Diamonds')
                ->where('orders.data.0.status.value', 'success')
                ->where('orders.data.0.url', route('invoice.show', $ownOrder))
                ->missing('orders.data.0.supplier_reference')
                ->missing('orders.data.0.supplier_message'));

        $response->assertDontSee('Order Orang Lain');
        $response->assertDontSee('Order Guest');
        $response->assertDontSee('SECRET-SUPPLIER-REF');
    }
}
