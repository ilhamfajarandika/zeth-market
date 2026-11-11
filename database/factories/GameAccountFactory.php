<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\GameAccount;
use App\Models\Product;
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
            'product_id' => Product::factory()->account(),
            'credentials' => 'email: '.fake()->safeEmail()."\npassword: ".fake()->password(10),
            'public_info' => 'Rank '.fake()->randomElement(['Epic', 'Legend', 'Mythic']).', '.fake()->numberBetween(20, 300).' skin',
            'status' => AccountStatus::Available,
        ];
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Reserved,
            'reserved_until' => now()->addMinutes(15),
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Sold,
            'sold_at' => now(),
        ]);
    }
}
