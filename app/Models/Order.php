<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'invoice', 'user_id', 'game_id', 'product_id', 'product_type', 'product_name',
    'target_user_id', 'target_zone_id', 'email', 'whatsapp',
    'price', 'fee', 'total', 'status',
    'payment_method', 'payment_reference', 'payment_url', 'payment_code', 'paid_at', 'expired_at',
    'supplier_reference', 'supplier_status', 'supplier_message', 'serial_number', 'completed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'status' => OrderStatus::class,
            'price' => 'decimal:2',
            'fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'invoice';
    }

    public static function generateInvoice(): string
    {
        return 'INV'.now()->format('ymd').Str::upper(Str::random(8));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Akun yang dipesan/dibeli (khusus product_type = account).
     *
     * @return HasOne<GameAccount, $this>
     */
    public function gameAccount(): HasOne
    {
        return $this->hasOne(GameAccount::class);
    }
}
