<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Exceptions\OutOfStockException;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Mengelola siklus hidup stok akun: available -> reserved -> sold,
 * atau reserved -> available jika pembayaran gagal/kedaluwarsa.
 *
 * Semua perubahan memakai transaksi + row lock agar satu akun
 * tidak pernah terjual ke dua pembeli.
 */
class AccountStockService
{
    /**
     * Lama akun ditahan untuk satu checkout (menit). Samakan dengan masa berlaku invoice payment.
     */
    public const RESERVATION_MINUTES = 15;

    /**
     * Tahan satu akun tersedia untuk order.
     *
     * @throws OutOfStockException
     */
    public function reserve(Product $product, Order $order, int $minutes = self::RESERVATION_MINUTES): GameAccount
    {
        return DB::transaction(function () use ($product, $order, $minutes): GameAccount {
            $account = GameAccount::query()
                ->whereBelongsTo($product)
                ->where('status', AccountStatus::Available)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($account === null) {
                throw new OutOfStockException("Stok akun untuk produk [{$product->name}] habis.");
            }

            $account->update([
                'status' => AccountStatus::Reserved,
                'order_id' => $order->id,
                'reserved_until' => now()->addMinutes($minutes),
            ]);

            return $account;
        });
    }

    /**
     * Tandai akun milik order sebagai terjual (dipanggil setelah pembayaran sukses).
     */
    public function markSold(Order $order): ?GameAccount
    {
        return DB::transaction(function () use ($order): ?GameAccount {
            $account = GameAccount::query()
                ->whereBelongsTo($order)
                ->lockForUpdate()
                ->first();

            if ($account === null || $account->status === AccountStatus::Sold) {
                return $account;
            }

            $account->update([
                'status' => AccountStatus::Sold,
                'reserved_until' => null,
                'sold_at' => now(),
            ]);

            return $account;
        });
    }

    /**
     * Kembalikan akun yang masih reserved milik order ke stok.
     */
    public function release(Order $order): void
    {
        GameAccount::query()
            ->whereBelongsTo($order)
            ->where('status', AccountStatus::Reserved)
            ->update([
                'status' => AccountStatus::Available,
                'order_id' => null,
                'reserved_until' => null,
            ]);
    }

    /**
     * Kembalikan semua reservasi yang sudah lewat waktu. Dijalankan scheduler tiap menit.
     *
     * @return int jumlah akun yang dikembalikan ke stok
     */
    public function releaseExpired(): int
    {
        return GameAccount::query()
            ->where('status', AccountStatus::Reserved)
            ->where('reserved_until', '<', now())
            ->update([
                'status' => AccountStatus::Available,
                'order_id' => null,
                'reserved_until' => null,
            ]);
    }
}
