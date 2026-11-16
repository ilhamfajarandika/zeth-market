<?php

namespace App\Services\Supplier;

use App\Enums\ProductType;
use App\Models\Game;
use App\Models\Product;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sinkron master katalog dari Melo: daftar brand (untuk menautkan game) dan item/produk per brand.
 *
 * Hanya memakai MeloService::brands() dan priceList(). Harga jual, gambar, dan deskripsi produk tidak
 * pernah ditimpa; admin yang mengisinya. Katalog berbagi 20 request/menit di sisi Melo.
 */
class MeloCatalogService
{
    public const BRANDS_CACHE_KEY = 'melo.catalog.brands';

    public const BRANDS_CACHE_SECONDS = 600;

    /**
     * Batas halaman per sync agar loop cursor tidak berjalan tanpa akhir.
     */
    private const MAX_PAGES = 50;

    public function __construct(private readonly MeloService $melo) {}

    /**
     * @return Collection<int, array{brand_id: int, name: string, slug: string, category: ?string, types: list<string>}>
     */
    public function brands(): Collection
    {
        return Cache::remember(self::BRANDS_CACHE_KEY, self::BRANDS_CACHE_SECONDS, function (): Collection {
            $body = $this->parse($this->melo->brands());

            return collect($body['data'] ?? [])
                ->map(fn (array $brand): array => [
                    'brand_id' => (int) $brand['brand_id'],
                    'name' => (string) $brand['name'],
                    'slug' => (string) ($brand['slug'] ?? ''),
                    'category' => $brand['category']['name'] ?? null,
                    'types' => collect($brand['type'] ?? [])->pluck('name')->filter()->values()->all(),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        });
    }

    /**
     * Pilihan Select di form Game ([brand_id => label]). Kosong jika Melo tidak bisa dihubungi.
     *
     * @return array<int, string>
     */
    public function brandOptions(): array
    {
        try {
            return $this->brands()
                ->mapWithKeys(fn (array $brand): array => [
                    $brand['brand_id'] => $brand['category'] ? "{$brand['name']} ({$brand['category']})" : $brand['name'],
                ])
                ->all();
        } catch (Throwable $e) {
            Log::warning('Gagal memuat brand Melo: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @return array{brand_id: int, name: string, slug: string, category: ?string, types: list<string>}|null
     */
    public function brand(int $brandId): ?array
    {
        return $this->brands()->firstWhere('brand_id', $brandId);
    }

    /**
     * Turunkan label/zone input dari form inquiry produk pertama brand (untuk mengisi form Game).
     *
     * @return array{requires_zone_id: bool, user_id_label: ?string, zone_id_label: ?string, thumbnail: ?string}
     */
    public function inquiryDefaults(int $brandId): array
    {
        $body = $this->parse($this->melo->priceList(['brand_id' => $brandId, 'limit' => 50, 'format' => 'compact']));
        $forms = $body['meta']['inquiry_forms'] ?? [];
        $formKey = collect($body['data'] ?? [])->pluck('inquiry_form_key')->filter()->first();
        $fields = collect(is_array($forms) && $formKey ? ($forms[$formKey]['fields'] ?? []) : []);

        $zone = $fields->firstWhere('key', 'customer_target_zone');

        return [
            'requires_zone_id' => $zone !== null,
            'user_id_label' => $fields->firstWhere('key', 'customer_target')['name'] ?? null,
            'zone_id_label' => $zone['name'] ?? null,
            'thumbnail' => collect($body['meta']['brands'] ?? [])->firstWhere('id', $brandId)['thumbnail'] ?? null,
        ];
    }

    /**
     * Impor/perbarui item Melo untuk satu game. Request HTTP selesai dulu; penulisan DB satu transaksi.
     *
     * Produk baru dibuat nonaktif dengan harga jual 0. Produk lama hanya diperbarui nama, grup, harga
     * modal, dan urutan; produk yang habis/hilang dari katalog dinonaktifkan (tidak diaktifkan kembali).
     *
     * @return array{created: int, updated: int, deactivated: int, skipped: int}
     */
    public function syncProducts(Game $game): array
    {
        if ($game->melo_brand_id === null) {
            throw new RuntimeException('Game belum ditautkan ke brand Melo.');
        }

        $items = $this->fetchBrandItems($game->melo_brand_id);
        if ($items->isEmpty()) {
            throw new RuntimeException('Katalog brand Melo kosong; sync dibatalkan agar produk tidak ikut dinonaktifkan.');
        }
        $result = ['created' => 0, 'updated' => 0, 'deactivated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($game, $items, &$result): void {
            $existing = Product::query()
                ->where('supplier', 'melo')
                ->whereIn('supplier_sku', $items->pluck('sku_code'))
                ->lockForUpdate()
                ->get()
                ->keyBy('supplier_sku');

            foreach ($items as $item) {
                $attributes = [
                    'name' => (string) $item['name'],
                    'group' => $item['type_name'] ?? null,
                    'base_price' => (int) ceil((float) $item['price']),
                    'sort' => (int) ($item['type_order'] ?? 0),
                ];
                $isActive = ($item['status'] ?? null) === 'active';
                $product = $existing->get($item['sku_code']);

                if ($product === null) {
                    Product::create($attributes + [
                        'game_id' => $game->id,
                        'type' => ProductType::Topup,
                        'supplier' => 'melo',
                        'supplier_sku' => $item['sku_code'],
                        'price' => 0,
                        'is_active' => false,
                    ]);
                    $result['created']++;

                    continue;
                }

                if ($product->game_id !== $game->id) {
                    $result['skipped']++;

                    continue;
                }

                $product->fill($attributes);
                if (! $isActive && $product->is_active) {
                    $product->is_active = false;
                    $result['deactivated']++;
                }
                if ($product->isDirty()) {
                    $product->save();
                    $result['updated']++;
                }
            }

            $result['deactivated'] += $game->products()
                ->where('supplier', 'melo')
                ->where('is_active', true)
                ->whereNotIn('supplier_sku', $items->pluck('sku_code'))
                ->update(['is_active' => false]);
        });

        return $result;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchBrandItems(int $brandId): Collection
    {
        $items = collect();
        $cursor = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $query = ['brand_id' => $brandId, 'format' => 'compact'];
            if ($cursor !== null) {
                $query['cursor'] = $cursor;
            }

            $body = $this->parse($this->melo->priceList($query));
            $items = $items->concat($body['data'] ?? []);

            $cursor = $body['meta']['pagination']['next_cursor'] ?? null;
            if ($cursor === null || ! ($body['meta']['pagination']['has_more'] ?? true)) {
                return $items->unique('sku_code')->values();
            }
        }

        throw new RuntimeException('Katalog Melo melebihi batas halaman sync.');
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(Response $response): array
    {
        $response->throw();

        $body = $response->json();
        if (! is_array($body) || ($body['success'] ?? true) === false) {
            throw new RuntimeException('Respons katalog Melo tidak valid.');
        }

        return $body;
    }
}
