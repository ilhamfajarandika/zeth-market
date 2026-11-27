<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

/**
 * Kartu sapaan admin. Lebar penuh dan paling atas agar tidak terjepit di antara widget statistik.
 * Lazy seperti widget lain: AccountWidget bawaan eager, sehingga di browser kartu ini tampil paling bawah.
 */
class WelcomeWidget extends AccountWidget
{
    protected static ?int $sort = -4;

    protected static ?bool $isLazyLoaded = true;

    protected int|string|array $columnSpan = 'full';
}
