<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\GameAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'credentials', 'public_info', 'status', 'order_id', 'reserved_until', 'sold_at'])]
#[Hidden(['credentials'])]
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
            'credentials' => 'encrypted',
            'status' => AccountStatus::class,
            'reserved_until' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
