<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Game;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basePrice = fake()->numberBetween(5, 500) * 1000;

        return [
            'game_id' => Game::factory(),
            'type' => ProductType::Topup,
            'name' => fake()->numberBetween(10, 2000).' Diamonds',
            'group' => 'Diamond',
            'supplier' => 'digiflazz',
            'supplier_sku' => fake()->unique()->bothify('SKU-####??'),
            'base_price' => $basePrice,
            'price' => $basePrice * 1.05,
            'reseller_price' => $basePrice * 1.02,
            'is_active' => true,
            'sort' => 0,
        ];
    }

    /**
     * Produk jual akun (stok dari tabel game_accounts, tanpa supplier).
     */
    public function account(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Account,
            'name' => 'Akun '.fake()->randomElement(['Epic', 'Legend', 'Mythic']).' #'.fake()->numberBetween(1, 99),
            'group' => 'Akun',
            'supplier' => null,
            'supplier_sku' => null,
        ]);
    }
}
