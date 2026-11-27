<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('melo:prune-nickname-cache')]
#[Description('Hapus baris cache cek nickname Melo yang sudah kedaluwarsa (retensi 24 jam)')]
class PruneNicknameCache extends Command
{
    public function handle(): int
    {
        $store = config('cache.stores.database');

        // Prefix cache ditambahkan ke setiap key (DatabaseStore); cocokkan dengan prefix yang sama.
        $deleted = DB::connection($store['connection'] ?? null)
            ->table($store['table'])
            ->where('key', 'like', config('cache.prefix').'melo.nickname.%')
            ->where('expiration', '<=', now()->getTimestamp())
            ->delete();

        $this->info("Dihapus {$deleted} baris cache cek nickname yang kedaluwarsa.");

        return self::SUCCESS;
    }
}
