<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\Game;
use App\Models\GameAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameAccount>
 */
class GameAccountFactory extends Factory
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
            'name' => 'Akun '.fake()->randomElement(['Epic', 'Legend', 'Mythic']).' #'.fake()->numberBetween(1, 99),
            'price' => fake()->numberBetween(50, 900) * 1000,
            'public_info' => 'Rank '.fake()->randomElement(['Epic', 'Legend', 'Mythic']).', '.fake()->numberBetween(20, 300).' skin',
            'status' => AccountStatus::Available,
        ];
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Sold,
            'sold_at' => now(),
        ]);
    }
}
