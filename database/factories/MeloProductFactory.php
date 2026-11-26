<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\MeloProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeloProduct>
 */
class MeloProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'sku_code' => fake()->unique()->bothify('SKU-####??'),
            'name' => fake()->numberBetween(10, 2000).' Diamonds',
            'type_name' => 'Diamond',
            'price' => fake()->numberBetween(5, 500) * 1000,
            'status' => 'active',
        ];
    }
}
