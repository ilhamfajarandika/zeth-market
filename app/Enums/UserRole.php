<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Member = 'member';
    case Reseller = 'reseller';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Member => 'Member',
            self::Reseller => 'Reseller',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Member => 'gray',
            self::Reseller => 'success',
        };
    }
}
