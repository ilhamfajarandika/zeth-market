<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';       // menunggu pembayaran
    case Paid = 'paid';             // sudah dibayar, belum diproses
    case Processing = 'processing'; // sedang diproses supplier
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Dibayar',
            self::Processing => 'Diproses',
            self::Success => 'Sukses',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Refunded => 'Refund',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Paid, self::Processing => 'info',
            self::Success => 'success',
            self::Failed => 'danger',
            self::Expired, self::Refunded => 'warning',
        };
    }

    /**
     * Status akhir: order tidak boleh berubah lagi oleh callback.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Success, self::Failed, self::Expired, self::Refunded], true);
    }
}
