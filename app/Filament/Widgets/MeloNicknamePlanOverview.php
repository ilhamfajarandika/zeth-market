<?php

namespace App\Filament\Widgets;

use App\Services\Supplier\MeloService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use Throwable;

/**
 * Kuota dan tarif cek nickname Melo (GET /check-nickname/plan). Di-cache agar buka dashboard tidak memanggil Melo terus.
 */
class MeloNicknamePlanOverview extends StatsOverviewWidget
{
    public const CACHE_KEY = 'melo.nickname_plan';

    public const CACHE_SECONDS = 600;

    protected static ?int $sort = -2;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $plan = $this->plan();

        if ($plan === null) {
            return [
                Stat::make('Cek nickname', 'Tidak dapat dimuat')
                    ->description('Periksa koneksi ke Melo.')
                    ->color('gray'),
            ];
        }

        return [
            Stat::make('Cek nickname gratis', Number::format($plan['free_daily_limit']).' / hari')
                ->description("Maks {$plan['free_minute_limit']} per menit, reset {$plan['resets_at']}")
                ->color('success'),
            Stat::make('Cek nickname berbayar', $plan['paid_price_mc'].' MC per cek')
                ->description("Maks {$plan['paid_minute_limit']} per menit")
                ->color('info'),
        ];
    }

    /**
     * @return array{free_daily_limit: int, free_minute_limit: int, paid_price_mc: int, paid_minute_limit: int, resets_at: string}|null null jika Melo tidak bisa dihubungi atau respons tidak lengkap
     */
    private function plan(): ?array
    {
        if (($cached = Cache::get(self::CACHE_KEY)) !== null) {
            return $cached;
        }

        try {
            $data = Validator::make(
                (array) app(MeloService::class)->nicknamePlan()->throw()->json('data'),
                [
                    'free_daily_limit' => ['required', 'integer'],
                    'free_minute_limit' => ['required', 'integer'],
                    'paid_price_mc' => ['required', 'integer'],
                    'paid_minute_limit' => ['required', 'integer'],
                    'resets_at' => ['required', 'string'],
                ],
            )->validate();
        } catch (Throwable $e) {
            Log::warning('Gagal memuat kuota cek nickname Melo: '.$e->getMessage());

            return null;
        }

        $plan = [
            'free_daily_limit' => (int) $data['free_daily_limit'],
            'free_minute_limit' => (int) $data['free_minute_limit'],
            'paid_price_mc' => (int) $data['paid_price_mc'],
            'paid_minute_limit' => (int) $data['paid_minute_limit'],
            'resets_at' => (string) $data['resets_at'],
        ];
        Cache::put(self::CACHE_KEY, $plan, self::CACHE_SECONDS);

        return $plan;
    }
}
