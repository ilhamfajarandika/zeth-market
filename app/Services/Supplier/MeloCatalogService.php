<?php

namespace App\Services\Supplier;

use App\Models\Game;
use App\Models\MeloProduct;
use App\Models\Product;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Sinkron master katalog dari Melo: daftar brand (untuk menautkan game) dan item/produk per brand.
 *
 * Hanya memakai MeloService::brands() dan priceList(). Harga jual produk tidak pernah ditimpa; admin yang
 * mengisinya. Katalog berbagi 20 request/menit di sisi Melo.
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
        // Yang disimpan di cache berupa array: config cache `serializable_classes` = false menolak objek Collection.
        return collect(Cache::remember(self::BRANDS_CACHE_KEY, self::BRANDS_CACHE_SECONDS, function (): array {
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
                ->values()
                ->all();
        }));
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
     * Unduh thumbnail brand Melo ke disk public agar storefront tidak bergantung pada CDN Melo.
     * Hanya https, maks 2 MB, dan isi file harus benar-benar WebP/JPEG/PNG (tipe dibaca dari isi, bukan header).
     *
     * @return string|null Path di disk public (mis. games/thumbnails/abc.webp), null bila gagal.
     */
    public function storeThumbnail(string $url): ?string
    {
        if (! str_starts_with($url, 'https://')) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($url);
        } catch (Throwable $e) {
            Log::warning('Gagal mengunduh thumbnail Melo: '.$e->getMessage());

            return null;
        }

        $body = $response->body();
        $extension = match (@getimagesizefromstring($body)['mime'] ?? null) {
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };

        if (! $response->successful() || $extension === null || strlen($body) > 2 * 1024 * 1024) {
            return null;
        }

        $path = 'games/thumbnails/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->put($path, $body);

        return $path;
    }

    /**
     * Simpan katalog Melo satu game ke `melo_products`, lalu perbarui produk yang sudah dipilih admin.
     * Request HTTP selesai dulu; penulisan DB satu transaksi.
     *
     * Tidak membuat produk (admin memilih lewat addProducts()). Produk terpilih hanya diperbarui nama,
     * grup, dan harga modal; yang habis/hilang dari katalog dinonaktifkan (tidak diaktifkan kembali).
     *
     * @return array{catalog: int, updated: int, deactivated: int}
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
        $skus = $items->pluck('sku_code');
        $result = ['catalog' => $items->count(), 'updated' => 0, 'deactivated' => 0];

        DB::transaction(function () use ($game, $items, $skus, &$result): void {
            $now = now();
            MeloProduct::upsert($items->map(fn (array $item): array => [
                'game_id' => $game->id,
                'sku_code' => (string) $item['sku_code'],
                'name' => (string) $item['name'],
                'type_name' => $item['type_name'] ?? null,
                'price' => (int) ceil((float) $item['price']),
                'status' => (string) ($item['status'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(), ['sku_code'], ['game_id', 'name', 'type_name', 'price', 'status', 'updated_at']);
            $game->meloProducts()->whereNotIn('sku_code', $skus)->delete();

            $existing = $game->products()
                ->where('supplier', 'melo')
                ->whereIn('supplier_sku', $skus)
                ->lockForUpdate()
                ->get()
                ->keyBy('supplier_sku');

            foreach ($items as $item) {
                $product = $existing->get($item['sku_code']);
                if ($product === null) {
                    continue;
                }

                $isActive = ($item['status'] ?? null) === 'active';
                $product->fill([
                    'name' => (string) $item['name'],
                    'group' => $item['type_name'] ?? null,
                    'base_price' => (int) ceil((float) $item['price']),
                ]);
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
                ->whereNotIn('supplier_sku', $skus)
                ->update(['is_active' => false]);
        });

        return $result;
    }

    /**
     * Jadikan item katalog Melo terpilih sebagai produk top up: nonaktif, harga jual 0 (admin yang mengisi).
     * SKU yang sudah menjadi produk dilewati.
     *
     * @param  iterable<MeloProduct>  $meloProducts
     * @return int Jumlah produk baru.
     */
    public function addProducts(iterable $meloProducts): int
    {
        $created = 0;

        foreach ($meloProducts as $item) {
            $product = Product::firstOrCreate(
                ['supplier' => 'melo', 'supplier_sku' => $item->sku_code],
                [
                    'game_id' => $item->game_id,
                    'name' => $item->name,
                    'group' => $item->type_name,
                    'base_price' => $item->price,
                    'price' => 0,
                    'is_active' => false,
                ],
            );
            $created += (int) $product->wasRecentlyCreated;
        }

        return $created;
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
