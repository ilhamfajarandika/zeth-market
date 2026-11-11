<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasColor, HasLabel
{
    /** Top up via supplier API (Digiflazz, dll.). */
    case Topup = 'topup';

    /** Penjualan akun game dari stok internal (tabel game_accounts). */
    case Account = 'account';

    public function getLabel(): string
    {
        return match ($this) {
            self::Topup => 'Top Up',
            self::Account => 'Akun Game',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Topup => 'info',
            self::Account => 'warning',
        };
    }
}
