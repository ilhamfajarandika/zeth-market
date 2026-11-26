<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrderViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_detail_is_grouped_into_sections_with_money_and_copyable_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->paid()->create([
            'price' => 10000,
            'fee' => 500,
            'total' => 10500,
            'target_user_id' => '123456789',
            'supplier_message' => 'Top up berhasil',
        ]);
        $this->actingAs($admin);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee('Ringkasan')
            ->assertSee('Rincian harga')
            ->assertSee('Produk & tujuan')
            ->assertSee('Pembeli')
            ->assertSee('Pembayaran')
            ->assertSee('Supplier (Melo)')
            ->assertSee($order->invoice)
            ->assertSee($order->product_name)
            ->assertSee('10.500')
            ->assertSee('123456789')
            ->assertSee('Top up berhasil');
    }

    public function test_order_detail_shows_placeholder_for_empty_payment_and_supplier_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);
        $this->actingAs($admin);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee('Kedaluwarsa pada')
            ->assertSee('Nomor serial')
            ->assertSee('-');
    }

    public function test_qris_order_shows_qr_code_and_type_instead_of_raw_payment_code(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'payment_method' => 'SP',
            'payment_code' => '00020101021226610016ID.CO.SHOPEE.WWW',
        ]);
        $this->actingAs($admin);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee('QRIS')
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertDontSee('00020101021226610016ID.CO.SHOPEE.WWW');
    }

    public function test_non_qris_order_shows_payment_code_text_and_its_type(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'payment_method' => 'BC',
            'payment_code' => '8808123456789',
        ]);
        $this->actingAs($admin);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee('Virtual Account')
            ->assertSee('8808123456789')
            ->assertDontSee('data:image/svg+xml;base64,', false);
    }
}
