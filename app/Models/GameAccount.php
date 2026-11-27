<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\GameAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Akun game yang dijual. Tidak menyimpan kredensial; penjualan dilakukan manual oleh admin.
 */
#[Fillable(['game_id', 'name', 'price', 'public_info', 'images', 'status', 'sold_at'])]
class GameAccount extends Model
{
    /** @use HasFactory<GameAccountFactory> */
    use HasFactory;

    /**
     * Status akun diubah manual oleh admin, jadi sold_at mengikuti status: terisi saat Terjual, kosong selain itu.
     */
    protected static function booted(): void
    {
        static::saving(function (GameAccount $account): void {
            if ($account->isDirty('status')) {
                $account->sold_at = $account->status === AccountStatus::Sold ? ($account->sold_at ?? now()) : null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'images' => 'array',
            'status' => AccountStatus::class,
            'sold_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
