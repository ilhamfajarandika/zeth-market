<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\GameAccounts\Pages\CreateGameAccount;
use App\Filament\Resources\GameAccounts\Pages\EditGameAccount;
use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ListGames;
use App\Filament\Resources\Games\RelationManagers\MeloProductsRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\MeloProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Supplier\MeloCatalogService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogResourceTest extends TestCase
{
    use RefreshDatabase;

    private const MELO_URL = 'https://melo.test/api/v1/h2h';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.melo.base_url' => self::MELO_URL,
            'services.melo.api_key' => 'k',
            'services.melo.secret_key' => 's',
        ]);
        Cache::forget(MeloCatalogService::BRANDS_CACHE_KEY);
        Cache::forget(MeloCatalogService::NICKNAME_GAMES_CACHE_KEY);
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function gameData(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Free Fire',
            'slug' => 'free-fire',
            'requires_zone_id' => false,
            'user_id_label' => 'User ID',
            'user_id_regex' => '^\d{6,12}$',
            'is_active' => true,
            'sort' => 0,
        ];
    }

    public function test_non_admin_cannot_open_catalog_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'member']));

        foreach (['/admin/categories', '/admin/games', '/admin/games/create', '/admin/products', '/admin/products/create', '/admin/game-accounts', '/admin/users'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_category_slug_is_generated_and_must_be_unique(): void
    {
        Livewire::test(ManageCategories::class)
            ->callAction(CreateAction::class, ['name' => 'Mobile Game', 'slug' => 'mobile-game', 'sort' => 0, 'is_active' => true])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['slug' => 'mobile-game']);

        Livewire::test(ManageCategories::class)
            ->callAction(CreateAction::class, ['name' => 'Lain', 'slug' => 'mobile-game', 'sort' => 0])
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_game_form_generates_slug_from_name_on_create(): void
    {
        Livewire::test(CreateGame::class)
            ->fillForm(['name' => 'Mobile Legends'])
            ->assertFormSet(['slug' => 'mobile-legends']);
    }

    public function test_admin_can_create_game_with_inactive_toggles(): void
    {
        Livewire::test(CreateGame::class)
            ->fillForm($this->gameData(['is_active' => false, 'is_popular' => false]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('games', ['slug' => 'free-fire', 'is_active' => false]);
    }

    public function test_game_slug_must_be_unique(): void
    {
        Game::factory()->create(['slug' => 'free-fire']);

        Livewire::test(CreateGame::class)
            ->fillForm($this->gameData())
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_game_regex_must_compile(): void
    {
        Livewire::test(CreateGame::class)
            ->fillForm($this->gameData(['user_id_regex' => '^(\d{6']))
            ->call('create')
            ->assertHasFormErrors(['user_id_regex']);
    }

    public function test_game_melo_game_code_is_saved_from_melo_nickname_games(): void
    {
        Http::fake([self::MELO_URL.'/check-nickname/games' => Http::response(['success' => true, 'games' => [
            ['game_code' => 'mobile-legends', 'name' => 'Mobile Legends', 'type' => 'game', 'zone_required' => true, 'zone_values' => null, 'params' => [], 'status' => 'available'],
        ]])]);

        Livewire::test(CreateGame::class)
            ->fillForm($this->gameData(['melo_game_code' => 'mobile-legends']))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('mobile-legends', Game::query()->where('slug', 'free-fire')->value('melo_game_code'));
    }

    public function test_saved_game_code_is_kept_when_melo_nickname_games_is_down(): void
    {
        Http::fake([self::MELO_URL.'/check-nickname/games' => Http::response(['success' => false], 502)]);
        $game = Game::factory()->create(['melo_game_code' => 'mobile-legends']);

        Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
            ->fillForm(['name' => 'Mobile Legends Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('mobile-legends', $game->refresh()->melo_game_code);
    }

    public function test_game_requiring_zone_must_define_zone_label_and_regex(): void
    {
        Livewire::test(CreateGame::class)
            ->fillForm($this->gameData(['requires_zone_id' => true, 'zone_id_label' => '', 'zone_id_regex' => '']))
            ->call('create')
            ->assertHasFormErrors(['zone_id_label' => 'required', 'zone_id_regex' => 'required']);
    }

    public function test_selecting_melo_brand_fills_game_defaults_without_overwriting_manual_input(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://melo.test/ml.png' => Http::response(UploadedFile::fake()->image('ml.png', 10, 10)->getContent()),
            self::MELO_URL.'/pricelists/categories' => Http::response(['success' => true, 'data' => [
                ['brand_id' => 10, 'name' => 'Mobile Legends', 'slug' => 'mobile-legends', 'category' => ['id' => 1, 'name' => 'Games', 'slug' => 'games'], 'type' => []],
            ]]),
            self::MELO_URL.'/pricelists*' => Http::response([
                'success' => true,
                'data' => [['name' => 'x', 'sku_code' => 'A', 'price' => 1, 'status' => 'active', 'inquiry_form_key' => 'f']],
                'meta' => [
                    'brands' => [['id' => 10, 'name' => 'Mobile Legends', 'thumbnail' => 'https://melo.test/ml.png', 'order' => 1]],
                    'inquiry_forms' => ['f' => ['fields' => [
                        ['key' => 'customer_target', 'name' => 'User ID'],
                        ['key' => 'customer_target_zone', 'name' => 'Zone ID'],
                    ]]],
                    'pagination' => ['next_cursor' => null, 'has_more' => false],
                ],
            ]),
        ]);

        Livewire::test(CreateGame::class)
            ->fillForm(['name' => 'ML Custom Name'])
            ->fillForm(['melo_brand_id' => 10])
            ->assertFormSet([
                'name' => 'ML Custom Name',
                'requires_zone_id' => true,
                'zone_id_label' => 'Zone ID',
            ])
            ->fillForm(['slug' => 'ml-custom', 'zone_id_regex' => '^\d{3,5}$'])
            ->call('create')
            ->assertHasNoFormErrors();

        $thumbnail = Game::query()->where('slug', 'ml-custom')->value('thumbnail');
        $this->assertStringStartsWith('games/thumbnails/', $thumbnail);
        Storage::disk('public')->assertExists($thumbnail);
    }

    public function test_game_thumbnail_is_uploaded_to_public_disk(): void
    {
        Storage::fake('public');

        Livewire::test(CreateGame::class)
            ->fillForm($this->gameData(['thumbnail' => UploadedFile::fake()->image('ff.png', 600, 600)]))
            ->call('create')
            ->assertHasNoFormErrors();

        $thumbnail = Game::query()->where('slug', 'free-fire')->value('thumbnail');
        $this->assertStringStartsWith('games/thumbnails/', $thumbnail);
        Storage::disk('public')->assertExists($thumbnail);
    }

    public function test_game_thumbnail_rejects_non_image(): void
    {
        Storage::fake('public');

        Livewire::test(CreateGame::class)
            ->fillForm($this->gameData(['thumbnail' => UploadedFile::fake()->create('evil.pdf', 10, 'application/pdf')]))
            ->call('create')
            ->assertHasFormErrors(['thumbnail']);
    }

    public function test_game_with_orders_cannot_be_deleted_from_edit_page(): void
    {
        $game = Order::factory()->create()->game;

        Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
            ->assertActionDisabled(DeleteAction::class);

        $this->assertModelExists($game);
    }

    public function test_game_without_orders_can_be_deleted_from_edit_page(): void
    {
        $game = Game::factory()->create();

        Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
            ->assertActionEnabled(DeleteAction::class)
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($game);
    }

    public function test_bulk_delete_skips_games_with_orders(): void
    {
        $ordered = Order::factory()->create()->game;
        $unused = Game::factory()->create();

        Livewire::test(ListGames::class)
            ->selectTableRecords([$ordered->getKey(), $unused->getKey()])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified();

        $this->assertModelExists($ordered);
        $this->assertModelMissing($unused);
    }

    public function test_sync_action_imports_items_for_linked_game(): void
    {
        Http::fake([self::MELO_URL.'/pricelists*' => Http::response([
            'success' => true,
            'data' => [['name' => '86 Diamonds', 'sku_code' => 'ML86', 'price' => 19000, 'type_name' => 'Diamond', 'type_order' => 1, 'status' => 'active']],
            'meta' => ['pagination' => ['next_cursor' => null, 'has_more' => false]],
        ])]);
        $game = Game::factory()->create(['melo_brand_id' => 10]);

        Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
            ->callAction('syncMeloProducts')
            ->assertNotified('Sync Melo selesai');

        $this->assertDatabaseHas('melo_products', ['game_id' => $game->id, 'sku_code' => 'ML86', 'price' => 19000]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_picks_melo_items_to_become_products(): void
    {
        $game = Game::factory()->create(['melo_brand_id' => 10]);
        $picked = MeloProduct::factory()->for($game)->create(['sku_code' => 'ML86', 'price' => 19000]);
        $other = MeloProduct::factory()->for($game)->create(['sku_code' => 'ML172']);

        Livewire::test(MeloProductsRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
            ->assertCanSeeTableRecords([$picked, $other])
            ->selectTableRecords([$picked->getKey()])
            ->callAction(TestAction::make('addProducts')->table()->bulk())
            ->assertNotified('1 produk ditambahkan');

        $this->assertDatabaseHas('products', ['game_id' => $game->id, 'supplier_sku' => 'ML86', 'base_price' => 19000, 'price' => 19000, 'is_active' => false]);
        $this->assertDatabaseMissing('products', ['supplier_sku' => 'ML172']);
    }

    public function test_sync_action_is_hidden_for_game_without_melo_brand_and_reports_failure(): void
    {
        $manual = Game::factory()->create(['melo_brand_id' => null]);
        Livewire::test(ListGames::class)->assertActionHidden(TestAction::make('syncMeloProducts')->table($manual));

        Http::fake([self::MELO_URL.'/pricelists*' => Http::response(['message' => 'down'], 500)]);
        $linked = Game::factory()->create(['melo_brand_id' => 11]);
        Product::factory()->for($linked)->create(['is_active' => true]);

        Livewire::test(EditGame::class, ['record' => $linked->getRouteKey()])
            ->callAction('syncMeloProducts')
            ->assertNotified('Sync Melo gagal');

        $this->assertSame(1, Product::where('is_active', true)->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function productData(Game $game, array $overrides = []): array
    {
        return $overrides + [
            'game_id' => $game->id,
            'name' => '86 Diamonds',
            'supplier' => 'melo',
            'supplier_sku' => 'ML86',
            'base_price' => 19000,
            'price' => 20000,
            'is_active' => true,
        ];
    }

    public function test_admin_can_create_topup_product_with_integer_prices(): void
    {
        $game = Game::factory()->create();
        MeloProduct::factory()->for($game)->create(['sku_code' => 'ML86']);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productData($game))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', ['supplier_sku' => 'ML86', 'base_price' => 19000, 'price' => 20000]);
    }

    public function test_topup_product_requires_supplier_and_sku(): void
    {
        $game = Game::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productData($game, ['supplier' => null, 'supplier_sku' => '']))
            ->call('create')
            ->assertHasFormErrors(['supplier' => 'required', 'supplier_sku' => 'required']);
    }

    public function test_negative_or_decimal_prices_are_rejected(): void
    {
        $game = Game::factory()->create();
        MeloProduct::factory()->for($game)->create(['sku_code' => 'NEW']);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productData($game, ['supplier_sku' => 'NEW', 'price' => -1, 'base_price' => '10.5']))
            ->call('create')
            ->assertHasFormErrors(['price', 'base_price']);
    }

    public function test_supplier_sku_must_be_unique_per_supplier(): void
    {
        $game = Game::factory()->create();
        MeloProduct::factory()->for($game)->create(['sku_code' => 'ML86']);
        Product::factory()->for($game)->create(['supplier' => 'melo', 'supplier_sku' => 'ML86']);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productData($game))
            ->call('create')
            ->assertHasFormErrors(['supplier_sku' => 'unique']);
    }

    public function test_picking_melo_sku_prefills_base_price_group_and_empty_name(): void
    {
        $game = Game::factory()->create();
        MeloProduct::factory()->for($game)->create(['sku_code' => 'ML86', 'name' => '86 Diamonds', 'type_name' => 'Diamond', 'price' => 19000]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['game_id' => $game->id, 'name' => null])
            ->fillForm(['supplier_sku' => 'ML86'])
            ->assertSchemaStateSet(['base_price' => 19000, 'group' => 'Diamond', 'name' => '86 Diamonds']);
    }

    public function test_sku_options_mark_cheapest_active_sku_per_name_as_best_price(): void
    {
        $game = Game::factory()->create();
        MeloProduct::factory()->for($game)->create(['sku_code' => 'A', 'name' => '86 Diamonds', 'price' => 20000, 'status' => 'active']);
        MeloProduct::factory()->for($game)->create(['sku_code' => 'B', 'name' => '86 diamonds ', 'price' => 19000, 'status' => 'active']);
        MeloProduct::factory()->for($game)->create(['sku_code' => 'C', 'name' => '86 Diamonds', 'price' => 18000, 'status' => 'out_of_stock']);
        MeloProduct::factory()->for($game)->create(['sku_code' => 'D', 'name' => 'Weekly Pass', 'price' => 27000, 'status' => 'active']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['game_id' => $game->id])
            ->assertFormFieldExists('supplier_sku', function (Select $field): bool {
                $best = collect($field->getOptions())->filter(fn (string $label): bool => str_starts_with($label, '★ Harga terbaik'));

                return $best->keys()->all() === ['B'];
            });
    }

    public function test_supplier_sku_must_be_a_melo_item_of_the_selected_game(): void
    {
        $game = Game::factory()->create();
        MeloProduct::factory()->for(Game::factory())->create(['sku_code' => 'ML86']);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productData($game))
            ->call('create')
            ->assertHasFormErrors(['supplier_sku' => 'in']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_whose_sku_left_melo_catalog_can_still_be_edited(): void
    {
        $product = Product::factory()->create(['supplier' => 'melo', 'supplier_sku' => 'GONE', 'price' => 1000]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['price' => 2000])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(2000, $product->refresh()->price);
    }

    public function test_price_can_be_edited_inline_and_unpriced_filter_works(): void
    {
        $unpriced = Product::factory()->create(['base_price' => 10000, 'price' => 0]);
        $priced = Product::factory()->create(['base_price' => 10000, 'price' => 11000]);

        Livewire::test(ListProducts::class)
            ->filterTable('unpriced', true)
            ->assertCanSeeTableRecords([$unpriced])
            ->assertCanNotSeeTableRecords([$priced])
            ->call('updateTableColumnState', 'price', (string) $unpriced->getKey(), '12500');

        $this->assertSame(12500, $unpriced->refresh()->price);
    }

    public function test_active_only_filter_hides_inactive_products(): void
    {
        $active = Product::factory()->create(['is_active' => true]);
        $inactive = Product::factory()->create(['is_active' => false]);

        Livewire::test(ListProducts::class)
            ->filterTable('active_only', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_bulk_set_price_applies_percent_markup_with_rounding_to_unpriced_products_only(): void
    {
        $unpriced = Product::factory()->create(['base_price' => 19000, 'price' => 0]);
        $priced = Product::factory()->create(['base_price' => 19000, 'price' => 30000]);

        Livewire::test(ListProducts::class)
            ->selectTableRecords([$unpriced->getKey(), $priced->getKey()])
            ->callAction(
                TestAction::make('setPrice')->table()->bulk(),
                data: ['mode' => 'percent', 'amount' => 5, 'round_to' => 500, 'only_unpriced' => true],
            )
            ->assertNotified();

        $this->assertSame(20000, $unpriced->refresh()->price); // 19000 * 1.05 = 19950 -> 20000
        $this->assertSame(30000, $priced->refresh()->price);
    }

    public function test_price_from_base_supports_fixed_markup_and_overwrite(): void
    {
        $this->assertSame(21000, Product::priceFromBase(19000, 'fixed', 1500, 1000));
        $this->assertSame(19950, Product::priceFromBase(19000, 'percent', 5, 1));
        $this->assertSame(1000, Product::priceFromBase(1000, 'fixed', 0, 0));
    }

    public function test_admin_can_create_game_account_with_any_status(): void
    {
        $game = Game::factory()->create();

        Livewire::test(CreateGameAccount::class)
            ->fillForm(['game_id' => $game->id, 'name' => 'Akun Mythic', 'price' => 350000, 'status' => AccountStatus::Disabled->value])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('game_accounts', ['game_id' => $game->id, 'name' => 'Akun Mythic', 'price' => 350000, 'status' => 'disabled', 'sold_at' => null]);
    }

    public function test_account_price_accepts_indonesian_thousands_separator(): void
    {
        $game = Game::factory()->create();

        Livewire::test(CreateGameAccount::class)
            ->fillForm(['game_id' => $game->id, 'name' => 'Akun Mythic', 'price' => '350.000', 'status' => AccountStatus::Available->value])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('game_accounts', ['name' => 'Akun Mythic', 'price' => 350000]);
    }

    public function test_account_price_input_renders_indonesian_money_mask(): void
    {
        Livewire::test(CreateGameAccount::class)
            ->assertSee("\$money(\$input, ',', '.', 0)", false);
    }

    public function test_game_accounts_table_has_no_credentials_column(): void
    {
        $this->assertFalse(Schema::hasColumn('game_accounts', 'credentials'));
    }

    public function test_admin_marking_account_sold_fills_sold_at_and_reverting_clears_it(): void
    {
        $account = GameAccount::factory()->create(['status' => AccountStatus::Available]);

        Livewire::test(EditGameAccount::class, ['record' => $account->getKey()])
            ->assertFormFieldEnabled('status')
            ->fillForm(['status' => AccountStatus::Sold->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(AccountStatus::Sold, $account->refresh()->status);
        $this->assertNotNull($account->sold_at);

        Livewire::test(EditGameAccount::class, ['record' => $account->getKey()])
            ->fillForm(['status' => AccountStatus::Available->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(AccountStatus::Available, $account->refresh()->status);
        $this->assertNull($account->sold_at);
    }

    public function test_account_images_are_stored_as_webp_on_public_disk(): void
    {
        Storage::fake('public');
        $game = Game::factory()->create();

        Livewire::test(CreateGameAccount::class)
            ->fillForm([
                'game_id' => $game->id,
                'name' => 'Akun Mythic',
                'price' => 350000,
                'status' => AccountStatus::Available->value,
                'images' => [UploadedFile::fake()->image('a.png', 300, 300), UploadedFile::fake()->image('b.jpg', 300, 300)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $images = GameAccount::query()->where('name', 'Akun Mythic')->firstOrFail()->images;

        $this->assertCount(2, $images);

        foreach ($images as $path) {
            $this->assertStringStartsWith('game-accounts/images/', $path);
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('public')->assertExists($path);
            $this->assertSame('WEBP', substr(Storage::disk('public')->get($path), 8, 4));
        }
    }

    public function test_account_images_are_limited_to_five(): void
    {
        Storage::fake('public');
        $game = Game::factory()->create();

        Livewire::test(CreateGameAccount::class)
            ->fillForm([
                'game_id' => $game->id,
                'name' => 'Akun Mythic',
                'price' => 350000,
                'status' => AccountStatus::Available->value,
                'images' => array_map(fn (int $number): UploadedFile => UploadedFile::fake()->image("img{$number}.png", 100, 100), range(1, 6)),
            ])
            ->call('create')
            ->assertHasFormErrors(['images']);

        $this->assertDatabaseMissing('game_accounts', ['name' => 'Akun Mythic']);
    }

    public function test_account_image_with_palette_png_is_stored_as_webp(): void
    {
        Storage::fake('public');
        $game = Game::factory()->create();

        $palette = imagecreate(20, 20);
        imagecolorallocate($palette, 200, 0, 0);
        ob_start();
        imagepng($palette);
        $png = ob_get_clean();

        Livewire::test(CreateGameAccount::class)
            ->fillForm([
                'game_id' => $game->id,
                'name' => 'Akun Mythic',
                'price' => 350000,
                'status' => AccountStatus::Available->value,
                'images' => [UploadedFile::fake()->createWithContent('palette.png', $png)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $path = GameAccount::query()->where('name', 'Akun Mythic')->firstOrFail()->images[0];

        Storage::disk('public')->assertExists($path);
        $this->assertSame('WEBP', substr(Storage::disk('public')->get($path), 8, 4));
    }
}
