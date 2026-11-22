<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\OrderStatus;
use App\Models\Banner;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_lists_only_active_games(): void
    {
        $visible = Game::factory()->create(['name' => 'Visible Game']);
        Game::factory()->for($visible->category)->create(['name' => 'Hidden Game', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('categories', 1)
                ->has('categories.0.games', 1)
                ->where('categories.0.games.0.name', 'Visible Game')
                ->where('categories.0.games.0.url', route('game.show', $visible)));
    }

    public function test_home_exposes_game_thumbnail_url_from_public_disk(): void
    {
        Game::factory()->create([
            'name' => 'Mobile Legends',
            'thumbnail' => 'icon-games/mobile-legends-bang-bang.webp',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('categories.0.games.0.thumbnailUrl', url('storage/icon-games/mobile-legends-bang-bang.webp')));
    }

    public function test_home_lists_only_active_banners_in_order(): void
    {
        $second = Banner::factory()->create(['title' => 'Second', 'sort' => 2]);
        $first = Banner::factory()->create(['title' => 'First', 'sort' => 1]);
        Banner::factory()->inactive()->create(['title' => 'Hidden', 'sort' => 0]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('banners', 2)
                ->where('banners.0.id', $first->id)
                ->where('banners.1.id', $second->id));
    }

    public function test_banner_without_alt_falls_back_to_title(): void
    {
        $banner = Banner::factory()->create(['title' => 'Promo Mingguan', 'alt' => null]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('banners.0.id', $banner->id)
                ->where('banners.0.alt', 'Promo Mingguan')
                ->where('banners.0.linkUrl', null));
    }

    public function test_inactive_game_page_returns_not_found(): void
    {
        $game = Game::factory()->create(['is_active' => false]);

        $this->get(route('game.show', $game))->assertNotFound();
    }

    public function test_storefront_shares_the_brand_logo(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('logoUrl', asset('images/logo.png')));
    }

    public function test_storefront_pages_link_the_logo_favicon(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('favicon-32x32.png', false)
            ->assertSee('apple-touch-icon.png', false);
    }

    public function test_game_page_lists_only_available_accounts(): void
    {
        $game = Game::factory()->create();
        GameAccount::factory()->for($game)->create(['name' => 'Akun Ready', 'price' => 100000, 'public_info' => 'Rank Mythic']);
        GameAccount::factory()->sold()->for($game)->create(['name' => 'Akun Terjual']);
        GameAccount::factory()->for($game)->create(['name' => 'Akun Nonaktif', 'status' => AccountStatus::Disabled]);
        GameAccount::factory()->for(Game::factory())->create(['name' => 'Akun Game Lain']);

        $this->get(route('game.show', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Game')
                ->has('accounts', 1)
                ->where('accounts.0.name', 'Akun Ready')
                ->where('accounts.0.publicInfo', 'Rank Mythic')
                ->where('accounts.0.price', Number::currency(100000, 'IDR', 'id')));
    }

    public function test_game_page_has_no_payment_channels_until_a_product_is_chosen(): void
    {
        Http::fake();
        $game = Game::factory()->create();

        $this->get(route('game.show', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paymentChannels', [])
                ->where('checkoutUrl', route('checkout.store', $game)));

        Http::assertNothingSent();
    }

    public function test_game_page_lists_payment_channels_with_fee_and_total_for_chosen_topup(): void
    {
        config(['services.duitku.merchant_code' => 'D1234', 'services.duitku.api_key' => 'key', 'services.duitku.sandbox' => true]);
        Http::fake(['*/paymentmethod/getpaymentmethod' => Http::response([
            'responseCode' => '00',
            'paymentFee' => [['paymentMethod' => 'SP', 'paymentName' => 'ShopeePay QRIS', 'paymentImage' => 'https://img.test/sp.png', 'totalFee' => '630']],
        ])]);
        $game = Game::factory()->create();
        $product = Product::factory()->for($game)->create(['price' => 9000]);

        $this->get(route('game.show', [$game, 'product' => $product->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paymentChannels', 1)
                ->where('paymentChannels.0.code', 'SP')
                ->where('paymentChannels.0.group', 'QRIS')
                ->where('paymentChannels.0.fee', Number::currency(630, 'IDR', 'id'))
                ->where('paymentChannels.0.total', Number::currency(9630, 'IDR', 'id')));

        Http::assertSent(fn ($request): bool => $request['amount'] === 9000);
    }

    public function test_game_page_offers_no_payment_for_foreign_products(): void
    {
        Http::fake();
        $game = Game::factory()->create();
        $foreign = Product::factory()->create();

        $this->get(route('game.show', [$game, 'product' => $foreign->id]))
            ->assertInertia(fn (Assert $page) => $page->where('paymentChannels', []));

        Http::assertNothingSent();
    }

    public function test_game_page_props_never_expose_internal_product_columns(): void
    {
        $game = Game::factory()->create();
        Product::factory()->for($game)->create([
            'base_price' => 11111,
            'supplier_sku' => 'SECRET-SKU-123',
        ]);

        $response = $this->get(route('game.show', $game))->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->missing('products.0.base_price')
            ->missing('products.0.supplier_sku'));
        $response->assertDontSee('SECRET-SKU-123');
    }

    public function test_game_page_requests_zone_id_only_when_required(): void
    {
        $withZone = Game::factory()->withZone()->create();
        $withoutZone = Game::factory()->create();

        $this->get(route('game.show', $withZone))
            ->assertInertia(fn (Assert $page) => $page->where('game.requiresZoneId', true)->where('game.zoneIdLabel', 'Zone ID'));
        $this->get(route('game.show', $withoutZone))
            ->assertInertia(fn (Assert $page) => $page->where('game.requiresZoneId', false));
    }

    public function test_invoice_shows_own_qris_image_only_while_pending(): void
    {
        $order = Order::factory()->create(['payment_method' => 'SP', 'payment_code' => '00020101021226610016ID.CO.SHOPEE.WWW']);

        $this->get(route('invoice.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.payment.qrImage', fn (string $image) => str_starts_with($image, 'data:image/svg+xml;base64,')));

        $order->update(['status' => OrderStatus::Paid]);

        $this->get(route('invoice.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.payment', null));
    }

    public function test_invoice_marks_final_status_so_page_stops_polling(): void
    {
        $order = Order::factory()->create();

        $this->get(route('invoice.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('order.status.isFinal', false));

        $order->update(['status' => OrderStatus::Expired]);

        $this->get(route('invoice.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('order.status.isFinal', true));
    }

    public function test_invoice_has_no_qr_image_for_virtual_account(): void
    {
        $order = Order::factory()->create(['payment_method' => 'BC', 'payment_code' => '8808123456789']);

        $this->get(route('invoice.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.payment.qrImage', null)->where('order.payment.code', '8808123456789'));
    }
}
