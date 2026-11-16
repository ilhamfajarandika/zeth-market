<?php

namespace App\Services\Supplier;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Saldo H2H Melo untuk dashboard admin dan peringatan saldo menipis.
 *
 * Hasil di-cache singkat supaya polling widget tidak menghabiskan kuota request Melo.
 */
class MeloBalanceService
{
    public const CACHE_KEY = 'melo.balance';

    public const CACHE_SECONDS = 60;

    public const LEVEL_OK = 'ok';

    public const LEVEL_LOW = 'low';

    public const LEVEL_CRITICAL = 'critical';

    public function __construct(private readonly MeloService $melo) {}

    /**
     * @return array{balance: int, is_sandbox: bool, fetched_at: string}|null null jika Melo tidak bisa dihubungi
     */
    public function get(bool $fresh = false): ?array
    {
        if (! $fresh && ($cached = Cache::get(self::CACHE_KEY)) !== null) {
            return $cached;
        }

        try {
            $response = $this->melo->balance()->throw();
            $data = $response->json('data');

            if (! is_array($data) || ! isset($data['h2h_balance']) || ! is_numeric($data['h2h_balance'])) {
                throw new \RuntimeException('Respons saldo Melo tidak valid.');
            }
        } catch (Throwable $e) {
            Log::warning('Gagal memuat saldo Melo: '.$e->getMessage());

            return null;
        }

        $snapshot = [
            'balance' => (int) floor((float) $data['h2h_balance']),
            'is_sandbox' => (bool) ($data['is_sandbox_mode'] ?? false),
            'fetched_at' => now()->toIso8601String(),
        ];
        Cache::put(self::CACHE_KEY, $snapshot, self::CACHE_SECONDS);

        return $snapshot;
    }

    public function threshold(): int
    {
        return max(0, (int) config('services.melo.low_balance_threshold'));
    }

    /**
     * @return self::LEVEL_*
     */
    public function level(int $balance): string
    {
        $threshold = $this->threshold();

        return match (true) {
            $balance <= intdiv($threshold, 2) => self::LEVEL_CRITICAL,
            $balance <= $threshold => self::LEVEL_LOW,
            default => self::LEVEL_OK,
        };
    }
}
