<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class PaymentOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $paidToday = Order::query()->whereDate('paid_at', today());
        $paidCount = (clone $paidToday)->count();
        $paidTotal = (int) (clone $paidToday)->sum('total');

        $awaiting = Order::query()
            ->where('status', OrderStatus::Pending)
            ->where(fn ($query) => $query->whereNull('expired_at')->orWhere('expired_at', '>', now()))
            ->count();

        $failedToday = Order::query()
            ->whereIn('status', [OrderStatus::Failed, OrderStatus::Expired])
            ->whereDate('updated_at', today())
            ->count();

        return [
            Stat::make('Dibayar hari ini', Number::currency($paidTotal, 'IDR', 'id', precision: 0))
                ->description("{$paidCount} pembayaran masuk"),
            Stat::make('Menunggu pembayaran', $awaiting)
                ->description('Invoice belum dibayar dan belum kedaluwarsa'),
            Stat::make('Gagal / kedaluwarsa hari ini', $failedToday)
                ->color($failedToday > 0 ? 'warning' : 'success'),
        ];
    }
}
