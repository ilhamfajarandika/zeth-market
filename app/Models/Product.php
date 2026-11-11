<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\ProductType;
use App\Enums\UserRole;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'game_id', 'type', 'name', 'group', 'image', 'description',
    'supplier', 'supplier_sku', 'base_price', 'price', 'reseller_price', 'strike_price',
    'is_active', 'sort',
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
            'base_price' => 'decimal:2',
            'price' => 'decimal:2',
            'reseller_price' => 'decimal:2',
            'strike_price' => 'decimal:2',
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
     * Harga jual sesuai role pembeli (guest/member pakai harga normal).
     */
    public function priceFor(?User $user): string
    {
        if ($user?->role === UserRole::Reseller && $this->reseller_price !== null) {
            return $this->reseller_price;
        }

        return $this->price;
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
        $query->where('is_active', true)->orderBy('sort')->orderBy('price');
    }
}
