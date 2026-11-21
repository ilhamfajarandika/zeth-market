<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\Game;
use App\Models\Product;
use App\Services\Supplier\MeloCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MeloCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://melo.test/api/v1/h2h';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.melo.base_url' => self::BASE_URL,
            'services.melo.api_key' => 'test-api-key',
            'services.melo.secret_key' => 'test-secret-key',
            'services.melo.sandbox' => true,
        ]);
        Cache::forget(MeloCatalogService::BRANDS_CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function item(string $sku, array $overrides = []): array
    {
        return $overrides + [
            'name' => "Item {$sku}",
            'sku_code' => $sku,
            'price' => 1000,
            'category_name' => 'Games',
            'brand_id' => 10,
            'type_name' => 'Diamonds',
            'category_order' => 1,
            'type_order' => 2,
            'status' => 'active',
            'inquiry_form_key' => 'form-a',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function page(array $items, ?string $nextCursor = null): array
    {
        return [
            'success' => true,
            'data' => $items,
            'meta' => [
                'brands' => [['id' => 10, 'name' => 'Alpha', 'thumbnail' => 'https://melo.test/ff.png', 'order' => 1]],
                'inquiry_forms' => [
                    'form-a' => ['fields' => [
                        ['key' => 'customer_target', 'name' => 'Player ID', 'type' => 'text'],
                        ['key' => 'customer_target_zone', 'name' => 'Zone', 'type' => 'text'],
                    ]],
                ],
                'pagination' => ['cursor' => null, 'next_cursor' => $nextCursor, 'limit' => 500, 'has_more' => $nextCursor !== null],
            ],
        ];
    }

    public function test_brands_are_mapped_sorted_and_cached(): void
    {
        Http::fake([self::BASE_URL.'/pricelists/categories' => Http::response(['success' => true, 'data' => [
            ['brand_id' => 20, 'name' => 'Zeta', 'slug' => 'zeta', 'category' => null, 'type' => []],
            ['brand_id' => 10, 'name' => 'Alpha', 'slug' => 'alpha', 'category' => ['id' => 1, 'name' => 'Games', 'slug' => 'games'], 'type' => [['id' => 1, 'name' => 'Diamonds', 'slug' => 'd']]],
        ]])]);

        $service = app(MeloCatalogService::class);

        $this->assertSame([10 => 'Alpha (Games)', 20 => 'Zeta'], $service->brandOptions());
        $this->assertSame(['Diamonds'], $service->brand(10)['types']);

        Http::assertSentCount(1);
    }

    public function test_brand_options_are_empty_when_melo_is_unreachable(): void
    {
        Http::fake([self::BASE_URL.'/pricelists/categories' => Http::response(['message' => 'down'], 500)]);

        $this->assertSame([], app(MeloCatalogService::class)->brandOptions());
    }

    public function test_inquiry_defaults_detect_zone_and_labels(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response($this->page([$this->item('A1')]))]);

        $this->assertSame([
            'requires_zone_id' => true,
            'user_id_label' => 'Player ID',
            'zone_id_label' => 'Zone',
            'thumbnail' => 'https://melo.test/ff.png',
        ], app(MeloCatalogService::class)->inquiryDefaults(10));
    }

    public function test_sync_creates_inactive_products_without_selling_price(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response($this->page([
            $this->item('A1', ['price' => 1000.2, 'name' => '86 Diamonds']),
            $this->item('A2'),
        ]))]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);

        $result = app(MeloCatalogService::class)->syncProducts($game);

        $this->assertSame(['created' => 2, 'updated' => 0, 'deactivated' => 0, 'skipped' => 0], $result);
        $product = Product::where('supplier_sku', 'A1')->first();
        $this->assertSame($game->id, $product->game_id);
        $this->assertSame(ProductType::Topup, $product->type);
        $this->assertSame('melo', $product->supplier);
        $this->assertSame('86 Diamonds', $product->name);
        $this->assertSame('Diamonds', $product->group);
        $this->assertSame(1001, $product->base_price);
        $this->assertSame(0, $product->price);
        $this->assertFalse($product->is_active);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'brand_id=10') && str_contains($request->url(), 'limit=500'));
    }

    public function test_sync_keeps_selling_price_and_active_flag_of_existing_products(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response($this->page([$this->item('A1', ['price' => 2000])]))]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);
        $product = Product::factory()->for($game)->create([
            'supplier_sku' => 'A1', 'base_price' => 1000, 'price' => 1500, 'strike_price' => 1800, 'image' => 'x.png', 'is_active' => true,
        ]);

        $result = app(MeloCatalogService::class)->syncProducts($game);

        $this->assertSame(['created' => 0, 'updated' => 1, 'deactivated' => 0, 'skipped' => 0], $result);
        $product->refresh();
        $this->assertSame(2000, $product->base_price);
        $this->assertSame(1500, $product->price);
        $this->assertSame(1800, $product->strike_price);
        $this->assertSame('x.png', $product->image);
        $this->assertTrue($product->is_active);
    }

    public function test_sync_deactivates_out_of_stock_and_missing_products(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response($this->page([
            $this->item('A1', ['status' => 'out_of_stock']),
            $this->item('A2'),
        ]))]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);
        $outOfStock = Product::factory()->for($game)->create(['supplier_sku' => 'A1', 'is_active' => true]);
        $missing = Product::factory()->for($game)->create(['supplier_sku' => 'GONE', 'is_active' => true]);

        $result = app(MeloCatalogService::class)->syncProducts($game);

        $this->assertSame(2, $result['deactivated']);
        $this->assertFalse($outOfStock->refresh()->is_active);
        $this->assertFalse($missing->refresh()->is_active);
        $this->assertFalse(Product::where('supplier_sku', 'A2')->first()->is_active);
    }

    public function test_sync_does_not_touch_manual_or_account_products(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response($this->page([$this->item('A1')]))]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);
        $account = Product::factory()->account()->for($game)->create(['is_active' => true]);

        app(MeloCatalogService::class)->syncProducts($game);

        $this->assertTrue($account->refresh()->is_active);
    }

    public function test_sync_skips_sku_that_belongs_to_another_game(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response($this->page([$this->item('A1', ['name' => 'Renamed'])]))]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);
        $other = Product::factory()->for(Game::factory())->create(['supplier_sku' => 'A1', 'name' => 'Original']);

        $result = app(MeloCatalogService::class)->syncProducts($game);

        $this->assertSame(1, $result['skipped']);
        $this->assertSame('Original', $other->refresh()->name);
    }

    public function test_sync_follows_cursor_until_the_last_page(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::sequence()
            ->push($this->page([$this->item('A1')], nextCursor: 'c2'))
            ->push($this->page([$this->item('A2')]))]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);

        $result = app(MeloCatalogService::class)->syncProducts($game);

        $this->assertSame(2, $result['created']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'cursor=c2'));
    }

    public function test_sync_failure_changes_nothing(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::sequence()
            ->push($this->page([$this->item('A1')], nextCursor: 'c2'))
            ->push(['message' => 'Too Many Requests'], 429)]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);

        try {
            app(MeloCatalogService::class)->syncProducts($game);
            $this->fail('Sync seharusnya gagal saat Melo membalas 429.');
        } catch (RequestException) {
            $this->assertSame(0, Product::count());
        }
    }

    public function test_sync_requires_linked_brand_and_non_empty_catalogue(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response($this->page([]))]);
        $service = app(MeloCatalogService::class);

        try {
            $service->syncProducts(Game::factory()->create(['melo_brand_id' => null]));
            $this->fail('Game tanpa brand Melo tidak boleh di-sync.');
        } catch (RuntimeException) {
            Http::assertNothingSent();
        }

        $game = Game::factory()->create(['melo_brand_id' => 10]);
        $kept = Product::factory()->for($game)->create(['is_active' => true]);

        $this->expectException(RuntimeException::class);
        try {
            $service->syncProducts($game);
        } finally {
            $this->assertTrue($kept->refresh()->is_active);
        }
    }
}
