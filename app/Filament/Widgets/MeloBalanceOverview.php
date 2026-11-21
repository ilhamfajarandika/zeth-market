<?php

namespace App\Filament\Widgets;

use App\Services\Supplier\MeloBalanceService;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class MeloBalanceOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $balances = app(MeloBalanceService::class);
        $snapshot = $balances->get();

        if ($snapshot === null) {
            return [
                Stat::make('Saldo Melo', 'Tidak dapat dimuat')
                    ->description('Periksa MELO_API_KEY / MELO_SECRET_KEY dan koneksi ke Melo.')
                    ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                    ->color('gray'),
            ];
        }

        $level = $balances->level($snapshot['balance']);
        $threshold = Number::currency($balances->threshold(), 'IDR', 'id', precision: 0);
        $updated = CarbonImmutable::parse($snapshot['fetched_at'])->timezone(config('app.timezone'))->format('H:i:s');

        [$color, $description, $icon] = match ($level) {
            MeloBalanceService::LEVEL_CRITICAL => ['danger', "Saldo hampir habis! Segera top up (batas {$threshold})", Heroicon::OutlinedExclamationCircle],
            MeloBalanceService::LEVEL_LOW => ['warning', "Saldo menipis, batas peringatan {$threshold}", Heroicon::OutlinedExclamationTriangle],
            default => ['success', "Saldo aman, batas peringatan {$threshold}", Heroicon::OutlinedCheckCircle],
        };

        return [
            Stat::make('Saldo Melo (H2H)', Number::currency($snapshot['balance'], 'IDR', 'id', precision: 0))
                ->description($description)
                ->descriptionIcon($icon)
                ->color($color),
            Stat::make('Mode Melo', $snapshot['is_sandbox'] ? 'Sandbox (simulasi)' : 'Production')
                ->description("Diperbarui {$updated}")
                ->color($snapshot['is_sandbox'] ? 'info' : 'gray'),
        ];
    }
}
