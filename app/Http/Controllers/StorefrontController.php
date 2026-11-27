<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\OrderStatus;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payment\DuitkuService;
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

        $products = $game->products()
            ->active()
            ->get()
            ->map(fn (Product $product): array => $this->productCard($product))
            ->values();

        $accounts = $game->gameAccounts()
            ->where('status', AccountStatus::Available)
            ->orderBy('price')
            ->get()
            ->map(fn (GameAccount $account): array => $this->accountCard($account))
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
            'products' => $products,
            'accounts' => $accounts,
            // Closure: Duitku hanya dipanggil saat pembeli memilih item (partial reload ?product=ID).
            'paymentChannels' => fn (): array => $this->paymentChannels($game, $request->integer('product'), $duitku),
            'checkoutUrl' => route('checkout.store', $game),
        ]);
    }

    public function invoice(Order $order): Response
    {
        $order->load('game');

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
                'paymentMethod' => $order->payment_method
                    ? $order->payment_method.' ('.DuitkuService::channelGroup($order->payment_method).')'
                    : null,
                'createdAt' => $order->created_at?->translatedFormat('d M Y, H:i'),
                'expiresAt' => $order->expired_at?->translatedFormat('d M Y, H:i'),
                'price' => $this->rupiah($order->price),
                'fee' => $this->rupiah($order->fee),
                'total' => $this->rupiah($order->total),
                'payment' => $order->status === OrderStatus::Pending
                    ? ['code' => $order->payment_code, 'url' => $order->payment_url, 'qrImage' => DuitkuService::qrisImage($order)]
                    : null,
                'serialNumber' => $isSuccessful ? $order->serial_number : null,
            ],
        ]);
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
     * @return array{id: int, name: string, group: ?string, price: string, strikePrice: ?string}
     */
    private function productCard(Product $product): array
    {
        $hasStrikePrice = $product->strike_price !== null && $product->strike_price > $product->price;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'group' => $product->group,
            'price' => $this->rupiah($product->price),
            'strikePrice' => $hasStrikePrice ? $this->rupiah($product->strike_price) : null,
        ];
    }

    /**
     * Akun yang dijual: hanya field yang boleh tampil (tanpa status internal atau kredensial).
     *
     * @return array{id: int, name: string, price: string, publicInfo: ?string}
     */
    private function accountCard(GameAccount $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'price' => $this->rupiah($account->price),
            'publicInfo' => $account->public_info,
        ];
    }

    /**
     * Metode bayar untuk produk top up terpilih. Nominal dan fee dihitung server dari harga produk.
     *
     * @return list<array{code: string, name: string, group: string, iconUrl: ?string, fee: string, total: string}>
     */
    private function paymentChannels(Game $game, int $productId, DuitkuService $duitku): array
    {
        $product = $game->products()->active()->find($productId);

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
