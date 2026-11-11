<?php

namespace App\Console\Commands;

use App\Services\AccountStockService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accounts:release-expired')]
#[Description('Kembalikan akun game yang reservasinya kedaluwarsa ke stok tersedia')]
class ReleaseExpiredAccounts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AccountStockService $stock): int
    {
        $released = $stock->releaseExpired();

        $this->info("{$released} akun dikembalikan ke stok.");

        return self::SUCCESS;
    }
}
