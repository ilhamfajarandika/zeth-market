<?php

namespace Tests\Feature;

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

    public function test_game_page_shows_account_stock(): void
    {
        $game = Game::factory()->create();
        $inStock = Product::factory()->account()->for($game)->create(['name' => 'Akun Ready', 'price' => 100000]);
        GameAccount::factory()->count(2)->for($inStock)->create();
        GameAccount::factory()->sold()->for($inStock)->create();
        Product::factory()->account()->for($game)->create(['name' => 'Akun Kosong', 'price' => 200000]);

        $this->get(route('game.show', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Game')
                ->where('productGroups.0.products.0.name', 'Akun Ready')
                ->where('productGroups.0.products.0.stock', 2)
                ->where('productGroups.0.products.1.name', 'Akun Kosong')
                ->where('productGroups.0.products.1.stock', 0));
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

    public function test_game_page_offers_no_payment_for_account_or_foreign_products(): void
    {
        Http::fake();
        $game = Game::factory()->create();
        $account = Product::factory()->account()->for($game)->create();
        $foreign = Product::factory()->create();

        foreach ([$account, $foreign] as $product) {
            $this->get(route('game.show', [$game, 'product' => $product->id]))
                ->assertInertia(fn (Assert $page) => $page->where('paymentChannels', []));
        }

        Http::assertNothingSent();
    }

    public function test_game_page_props_never_expose_internal_product_columns(): void
    {
        $game = Game::factory()->create();
        Product::factory()->for($game)->create([
            'base_price' => 11111,
            'reseller_price' => 22222,
            'supplier_sku' => 'SECRET-SKU-123',
        ]);

        $response = $this->get(route('game.show', $game))->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->missing('productGroups.0.products.0.base_price')
            ->missing('productGroups.0.products.0.reseller_price')
            ->missing('productGroups.0.products.0.supplier_sku'));
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

    public function test_invoice_hides_account_credentials_until_order_succeeds(): void
    {
        $product = Product::factory()->account()->create();
        $order = Order::factory()->for($product)->create();
        GameAccount::factory()->reserved()->for($product)->create(['order_id' => $order->id, 'credentials' => 'secret-login']);

        $this->get(route('invoice.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Invoice')->where('order.credentials', null))
            ->assertDontSee('secret-login');

        $order->update(['status' => OrderStatus::Success]);

        $this->get(route('invoice.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.credentials', 'secret-login'));
    }

    public function test_invoice_shows_own_qris_image_only_while_pending(): void
    {
        $order = Order::factory()->create(['payment_method' => 'SP', 'payment_code' => '00020101021226610016ID.CO.SHOPEE.WWW']);

        $this->get(route('invoice.show', $order))->assertOk()->assertSee('data:image/svg+xml;base64,', false);

        $order->update(['status' => OrderStatus::Paid]);

        $this->get(route('invoice.show', $order))->assertOk()->assertDontSee('data:image/svg+xml;base64,', false);
    }
}
