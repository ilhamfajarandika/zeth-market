<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountStatus: string implements HasColor, HasLabel
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Disabled = 'disabled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Reserved => 'Dipesan',
            self::Sold => 'Terjual',
            self::Disabled => 'Nonaktif',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Reserved => 'warning',
            self::Sold => 'gray',
            self::Disabled => 'danger',
        };
    }
}
