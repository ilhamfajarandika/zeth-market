<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Game::factory()->create(['name' => 'Visible Game']);
        Game::factory()->create(['name' => 'Hidden Game', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Visible Game')
            ->assertDontSee('Hidden Game');
    }

    public function test_inactive_game_page_returns_not_found(): void
    {
        $game = Game::factory()->create(['is_active' => false]);

        $this->get(route('game.show', $game))->assertNotFound();
    }

    public function test_game_page_shows_account_stock(): void
    {
        $game = Game::factory()->create();
        $inStock = Product::factory()->account()->for($game)->create(['name' => 'Akun Ready']);
        GameAccount::factory()->count(2)->for($inStock)->create();
        GameAccount::factory()->sold()->for($inStock)->create();
        Product::factory()->account()->for($game)->create(['name' => 'Akun Kosong']);

        $this->get(route('game.show', $game))
            ->assertOk()
            ->assertSeeInOrder(['Akun Ready', 'Stok: 2'])
            ->assertSeeInOrder(['Akun Kosong', 'Stok habis']);
    }

    public function test_game_page_shows_zone_input_only_when_required(): void
    {
        $withZone = Game::factory()->withZone()->create();
        $withoutZone = Game::factory()->create();

        $this->get(route('game.show', $withZone))->assertSee('Zone ID');
        $this->get(route('game.show', $withoutZone))->assertDontSee('Zone ID');
    }

    public function test_invoice_hides_account_credentials_until_order_succeeds(): void
    {
        $product = Product::factory()->account()->create();
        $order = Order::factory()->for($product)->create();
        GameAccount::factory()->reserved()->for($product)->create(['order_id' => $order->id, 'credentials' => 'secret-login']);

        $this->get(route('invoice.show', $order))->assertOk()->assertDontSee('secret-login');

        $order->update(['status' => OrderStatus::Success]);

        $this->get(route('invoice.show', $order))->assertOk()->assertSee('secret-login');
    }
}
