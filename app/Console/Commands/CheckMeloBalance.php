<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Supplier\MeloBalanceService;
use Filament\Notifications\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

#[Signature('melo:check-balance')]
#[Description('Cek saldo H2H Melo dan kirim notifikasi ke admin jika menipis')]
class CheckMeloBalance extends Command
{
    /**
     * Jeda minimal antar notifikasi untuk tingkat yang sama.
     */
    private const REPEAT_HOURS = 6;

    private const ALERT_CACHE_KEY = 'melo.balance.alerted_level';

    public function handle(MeloBalanceService $balances): int
    {
        $snapshot = $balances->get(fresh: true);

        if ($snapshot === null) {
            $this->error('Saldo Melo tidak bisa dimuat. Notifikasi tidak dikirim.');

            return self::FAILURE;
        }

        $level = $balances->level($snapshot['balance']);
        $formatted = Number::currency($snapshot['balance'], 'IDR', 'id', precision: 0);
        $this->info("Saldo Melo {$formatted} (level: {$level}).");

        if ($level === MeloBalanceService::LEVEL_OK) {
            Cache::forget(self::ALERT_CACHE_KEY);

            return self::SUCCESS;
        }

        // Saldo simulasi mode sandbox tidak perlu memicu peringatan.
        if ($snapshot['is_sandbox']) {
            return self::SUCCESS;
        }

        // Kirim sekali per tingkat; tingkat yang memburuk (low -> critical) langsung dikirim ulang.
        if (Cache::get(self::ALERT_CACHE_KEY) === $level) {
            return self::SUCCESS;
        }
        Cache::put(self::ALERT_CACHE_KEY, $level, now()->addHours(self::REPEAT_HOURS));

        $threshold = Number::currency($balances->threshold(), 'IDR', 'id', precision: 0);
        $notification = Notification::make()
            ->title($level === MeloBalanceService::LEVEL_CRITICAL ? 'Saldo Melo hampir habis' : 'Saldo Melo menipis')
            ->body("Saldo H2H tinggal {$formatted} (batas peringatan {$threshold}). Top up saldo agar pesanan tidak gagal dikirim ke supplier.");
        $level === MeloBalanceService::LEVEL_CRITICAL ? $notification->danger() : $notification->warning();

        $notification->sendToDatabase(User::query()->where('role', UserRole::Admin)->get());

        return self::SUCCESS;
    }
}
