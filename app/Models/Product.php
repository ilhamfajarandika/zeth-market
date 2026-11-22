<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'game_id', 'type', 'name', 'group',
    'supplier', 'supplier_sku', 'base_price', 'price', 'strike_price', 'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'base_price' => 'integer',
            'price' => 'integer',
            'strike_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return HasMany<GameAccount, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(GameAccount::class);
    }

    /**
     * @return HasMany<GameAccount, $this>
     */
    public function availableAccounts(): HasMany
    {
        return $this->accounts()->where('status', AccountStatus::Available);
    }

    public function isAccount(): bool
    {
        return $this->type === ProductType::Account;
    }

    /**
     * Harga jual dari harga modal: markup persen atau Rupiah tetap, dibulatkan ke atas ke kelipatan tertentu.
     *
     * @param  'percent'|'fixed'  $mode
     */
    public static function priceFromBase(int $basePrice, string $mode, int $amount, int $roundTo = 1): int
    {
        $price = $mode === 'percent'
            ? (int) ceil($basePrice * (100 + $amount) / 100)
            : $basePrice + $amount;

        $roundTo = max(1, $roundTo);

        return (int) (ceil($price / $roundTo) * $roundTo);
    }

    /**
     * Eager-load jumlah stok akun tersedia sebagai `available_accounts_count`.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function withStock(Builder $query): void
    {
        $query->withCount('availableAccounts');
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('price');
    }
}
