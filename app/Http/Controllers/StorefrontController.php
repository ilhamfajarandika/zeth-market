<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Game;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payment\DuitkuService;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman storefront (Inertia + Vue). Props ditulis eksplisit: semua prop Inertia
 * tercetak sebagai JSON di sumber halaman, jadi kolom internal (harga modal, SKU
 * supplier, kredensial) tidak boleh ikut terkirim tanpa sengaja.
 */
class StorefrontController extends Controller
{
    public function home(): Response
    {
        $categories = Category::query()
            ->active()
            ->with(['games' => fn ($query) => $query->active()])
            ->get();

        $banners = Banner::query()
            ->active()
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => $banner->id,
                'imageUrl' => Storage::disk('public')->url($banner->image),
                'alt' => $banner->alt ?? $banner->title ?? 'Banner promo',
                'linkUrl' => $banner->link_url,
            ])
            ->values();

        return Inertia::render('Home', [
            'banners' => $banners,
            'categories' => $categories->map(fn (Category $category): array => [
                'slug' => $category->slug,
                'name' => $category->name,
                'games' => $category->games->map(fn (Game $game): array => $this->gameSummary($game))->values(),
            ])->values(),
        ]);
    }

    public function game(Request $request, Game $game, DuitkuService $duitku): Response
    {
        abort_unless($game->is_active, 404);

        $productGroups = $game->products()
            ->active()
            ->withStock()
            ->get()
            ->groupBy(fn (Product $product): string => $product->type->getLabel())
            ->map(fn ($products, string $label): array => [
                'label' => $label,
                'products' => $products->map(fn (Product $product): array => $this->productCard($product))->values(),
            ])
            ->values();

        return Inertia::render('Game', [
            'game' => [
                ...$this->gameSummary($game),
                'bannerUrl' => $game->banner ? Storage::disk('public')->url($game->banner) : null,
                'instruction' => $game->instruction,
                'requiresZoneId' => $game->requires_zone_id,
                'userIdLabel' => $game->user_id_label,
                'zoneIdLabel' => $game->zone_id_label,
            ],
            'productGroups' => $productGroups,
            // Closure: Duitku hanya dipanggil saat pembeli memilih item (partial reload ?product=ID).
            'paymentChannels' => fn (): array => $this->paymentChannels($game, $request->integer('product'), $duitku),
            'checkoutUrl' => route('checkout.store', $game),
        ]);
    }

    public function invoice(Order $order): Response
    {
        $order->load(['game', 'gameAccount']);

        $isSuccessful = $order->status === OrderStatus::Success;

        return Inertia::render('Invoice', [
            'order' => [
                'invoice' => $order->invoice,
                'status' => [
                    'value' => $order->status->value,
                    'label' => $order->status->getLabel(),
                    'color' => $order->status->getColor(),
                    'isFinal' => $order->status->isFinal(),
                ],
                'gameName' => $order->game->name,
                'productName' => $order->product_name,
                'target' => $order->target_user_id
                    ? $order->target_user_id.($order->target_zone_id ? ' ('.$order->target_zone_id.')' : '')
                    : null,
                'paymentMethod' => $order->payment_method,
                'createdAt' => $order->created_at?->translatedFormat('d M Y, H:i'),
                'expiresAt' => $order->expired_at?->translatedFormat('d M Y, H:i'),
                'price' => $this->rupiah($order->price),
                'fee' => $this->rupiah($order->fee),
                'total' => $this->rupiah($order->total),
                'payment' => $order->status === OrderStatus::Pending
                    ? ['code' => $order->payment_code, 'url' => $order->payment_url, 'qrImage' => $this->qrisImage($order)]
                    : null,
                'serialNumber' => $isSuccessful ? $order->serial_number : null,
                'credentials' => $isSuccessful ? $order->gameAccount?->credentials : null,
            ],
        ]);
    }

    /**
     * QR (data URI SVG) dari `qrString` Duitku untuk metode QRIS; null untuk metode lain.
     */
    private function qrisImage(Order $order): ?string
    {
        if (blank($order->payment_code) || DuitkuService::channelGroup((string) $order->payment_method) !== 'QRIS') {
            return null;
        }

        return (new QRCode)->render($order->payment_code);
    }

    /**
     * @return array{slug: string, name: string, publisher: ?string, url: string, thumbnailUrl: ?string, isPopular: bool}
     */
    private function gameSummary(Game $game): array
    {
        return [
            'slug' => $game->slug,
            'name' => $game->name,
            'publisher' => $game->publisher,
            'url' => route('game.show', $game),
            'thumbnailUrl' => $game->thumbnail ? Storage::disk('public')->url($game->thumbnail) : null,
            'isPopular' => $game->is_popular,
        ];
    }

    /**
     * @return array{id: int, name: string, price: string, strikePrice: ?string, isAccount: bool, stock: ?int}
     */
    private function productCard(Product $product): array
    {
        $hasStrikePrice = $product->strike_price !== null && $product->strike_price > $product->price;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $this->rupiah($product->price),
            'strikePrice' => $hasStrikePrice ? $this->rupiah($product->strike_price) : null,
            'isAccount' => $product->isAccount(),
            'stock' => $product->isAccount() ? $product->available_accounts_count : null,
        ];
    }

    /**
     * Metode bayar untuk produk top up terpilih. Nominal dan fee dihitung server dari harga produk.
     *
     * @return list<array{code: string, name: string, group: string, iconUrl: ?string, fee: string, total: string}>
     */
    private function paymentChannels(Game $game, int $productId, DuitkuService $duitku): array
    {
        $product = $game->products()->active()->where('type', ProductType::Topup)->find($productId);

        if ($product === null) {
            return [];
        }

        return array_map(fn (array $channel): array => [
            ...$channel,
            'fee' => $this->rupiah($channel['fee']),
            'total' => $this->rupiah($product->price + $channel['fee']),
        ], $duitku->paymentChannels($product->price));
    }

    private function rupiah(string|int|float|null $amount): string
    {
        return Number::currency((float) $amount, 'IDR', 'id');
    }
}
