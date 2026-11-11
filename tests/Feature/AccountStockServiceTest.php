<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Exceptions\OutOfStockException;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use App\Services\AccountStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountStockService $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stock = new AccountStockService;
    }

    public function test_reserve_holds_one_available_account_for_the_order(): void
    {
        $product = Product::factory()->account()->create();
        GameAccount::factory()->count(2)->for($product)->create();
        $order = Order::factory()->for($product)->create();

        $this->freezeSecond();
        $account = $this->stock->reserve($product, $order);

        $this->assertSame(AccountStatus::Reserved, $account->status);
        $this->assertTrue($account->order->is($order));
        $this->assertEquals(now()->addMinutes(AccountStockService::RESERVATION_MINUTES), $account->reserved_until);
        $this->assertSame(1, $product->availableAccounts()->count());
    }

    public function test_reserve_throws_when_no_account_is_available(): void
    {
        $product = Product::factory()->account()->create();
        GameAccount::factory()->sold()->for($product)->create();
        $order = Order::factory()->for($product)->create();

        $this->expectException(OutOfStockException::class);

        $this->stock->reserve($product, $order);
    }

    public function test_two_orders_never_receive_the_same_account(): void
    {
        $product = Product::factory()->account()->create();
        GameAccount::factory()->count(2)->for($product)->create();

        $first = $this->stock->reserve($product, Order::factory()->for($product)->create());
        $second = $this->stock->reserve($product, Order::factory()->for($product)->create());

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(0, $product->availableAccounts()->count());
    }

    public function test_mark_sold_finalizes_the_reserved_account(): void
    {
        $product = Product::factory()->account()->create();
        GameAccount::factory()->for($product)->create();
        $order = Order::factory()->for($product)->create();
        $this->stock->reserve($product, $order);

        $account = $this->stock->markSold($order);

        $this->assertSame(AccountStatus::Sold, $account->status);
        $this->assertNull($account->reserved_until);
        $this->assertNotNull($account->sold_at);
    }

    public function test_release_returns_the_reserved_account_to_stock(): void
    {
        $product = Product::factory()->account()->create();
        $account = GameAccount::factory()->for($product)->create();
        $order = Order::factory()->for($product)->create();
        $this->stock->reserve($product, $order);

        $this->stock->release($order);

        $account->refresh();
        $this->assertSame(AccountStatus::Available, $account->status);
        $this->assertNull($account->order_id);
    }

    public function test_release_expired_only_releases_reservations_past_their_deadline(): void
    {
        $product = Product::factory()->account()->create();
        $expired = GameAccount::factory()->reserved()->for($product)->create(['reserved_until' => now()->subMinute()]);
        $active = GameAccount::factory()->reserved()->for($product)->create(['reserved_until' => now()->addMinutes(5)]);

        $released = $this->stock->releaseExpired();

        $this->assertSame(1, $released);
        $this->assertSame(AccountStatus::Available, $expired->refresh()->status);
        $this->assertSame(AccountStatus::Reserved, $active->refresh()->status);
    }

    public function test_release_expired_command_runs_successfully(): void
    {
        $product = Product::factory()->account()->create();
        GameAccount::factory()->reserved()->for($product)->create(['reserved_until' => now()->subMinute()]);

        $this->artisan('accounts:release-expired')
            ->expectsOutput('1 akun dikembalikan ke stok.')
            ->assertSuccessful();
    }

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $account = GameAccount::factory()->create(['credentials' => 'email: a@b.c']);

        $raw = GameAccount::query()->toBase()->where('id', $account->id)->value('credentials');

        $this->assertNotSame('email: a@b.c', $raw);
        $this->assertSame('email: a@b.c', $account->refresh()->credentials);
    }
}
