<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\CheckoutRequest;
use App\Models\Game;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payment\DuitkuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Checkout top up: buat order pending (snapshot harga), buat transaksi Duitku, lalu ke invoice.
 * Fee ditanggung pembeli (keputusan PO 2026-11-21): total = harga produk + totalFee metode dari Duitku.
 */
class CheckoutController extends Controller
{
    /**
     * Sama dengan default expiryPeriodInMinutes() Duitku (keputusan #9, rekomendasi 15 menit).
     */
    public const INVOICE_MINUTES = 15;

    public function store(CheckoutRequest $request, Game $game, DuitkuService $duitku): RedirectResponse
    {
        abort_unless($game->is_active, 404);

        $product = Product::query()->findOrFail($request->integer('product_id'));
        $method = $request->string('payment_method')->toString();

        $channel = collect($duitku->paymentChannels($product->price))->firstWhere('code', $method);
        if ($channel === null) {
            throw ValidationException::withMessages(['payment_method' => 'Metode pembayaran tidak tersedia. Pilih metode lain.']);
        }

        $order = DB::transaction(fn (): Order => Order::create([
            'invoice' => Order::generateInvoice(),
            'user_id' => $request->user()?->id,
            'game_id' => $game->id,
            'product_id' => $product->id,
            'product_type' => $product->type,
            'product_name' => $product->name,
            'target_user_id' => $request->string('target_user_id')->trim()->toString(),
            'target_zone_id' => $game->requires_zone_id ? $request->string('target_zone_id')->trim()->toString() : null,
            'email' => $request->input('email'),
            'whatsapp' => $request->string('whatsapp')->toString(),
            'price' => $product->price,
            'fee' => $channel['fee'],
            'total' => $product->price + $channel['fee'],
            'status' => OrderStatus::Pending,
            'payment_method' => $method,
            'expired_at' => now()->addMinutes(self::INVOICE_MINUTES),
        ]));

        // HTTP ke Duitku di luar transaksi DB: order tetap tercatat walau Duitku gagal.
        try {
            $isCreated = $duitku->storePaymentDetails($order, $duitku->createTransaction($order, $method));
        } catch (Throwable $e) {
            Log::warning('Gagal membuat transaksi Duitku.', ['invoice' => $order->invoice, 'error' => $e->getMessage()]);
            $isCreated = false;
        }

        if (! $isCreated) {
            DB::transaction(fn () => Order::query()->lockForUpdate()->find($order->id)->update(['status' => OrderStatus::Failed]));

            throw ValidationException::withMessages(['payment_method' => 'Pembayaran gagal dibuat, silakan coba lagi atau pilih metode lain.']);
        }

        return to_route('invoice.show', $order);
    }
}
