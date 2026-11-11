<?php

namespace App\Filament\Widgets;

use App\Enums\AccountStatus;
use App\Enums\OrderStatus;
use App\Models\GameAccount;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StoreOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $todayRevenue = Order::query()
            ->where('status', OrderStatus::Success)
            ->whereDate('created_at', today())
            ->sum('total');

        $todaySuccess = Order::query()
            ->where('status', OrderStatus::Success)
            ->whereDate('created_at', today())
            ->count();

        $pendingOrders = Order::query()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])
            ->count();

        $availableAccounts = GameAccount::query()
            ->where('status', AccountStatus::Available)
            ->count();

        return [
            Stat::make('Omzet hari ini', Number::currency($todayRevenue, 'IDR', 'id'))
                ->description("{$todaySuccess} transaksi sukses"),
            Stat::make('Perlu diproses', $pendingOrders)
                ->description('Dibayar / sedang diproses supplier')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),
            Stat::make('Stok akun tersedia', $availableAccounts)
                ->color($availableAccounts > 5 ? 'success' : 'danger'),
        ];
    }
}
