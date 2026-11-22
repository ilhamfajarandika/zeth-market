<?php

namespace App\Models;

use Database\Factories\MeloProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Item katalog Melo hasil sync per game. Admin memilih item mana yang dijadikan `products`.
 */
#[Fillable(['game_id', 'sku_code', 'name', 'type_name', 'price', 'status'])]
class MeloProduct extends Model
{
    /** @use HasFactory<MeloProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
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
     * Produk toko yang dibuat dari item ini (jika sudah dipilih).
     *
     * @return HasOne<Product, $this>
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'supplier_sku', 'sku_code')->where('supplier', 'melo');
    }
}
