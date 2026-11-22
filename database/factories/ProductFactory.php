<?php

namespace Database\Factories;

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
            'name' => fake()->numberBetween(10, 2000).' Diamonds',
            'group' => 'Diamond',
            'supplier' => 'melo',
            'supplier_sku' => fake()->unique()->bothify('SKU-####??'),
            'base_price' => $basePrice,
            'price' => (int) round($basePrice * 1.05),
            'is_active' => true,
        ];
    }
}
