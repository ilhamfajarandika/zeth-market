<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
