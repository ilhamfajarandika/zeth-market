<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'game_id', 'name', 'group',
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
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('price');
    }
}
