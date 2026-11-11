<?php

namespace Database\Factories;

use App\Models\ApiLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiLog>
 */
class ApiLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['tripay', 'melo']),
            'direction' => fake()->randomElement(['outgoing', 'callback']),
            'reference' => fake()->uuid(),
            'request' => [],
            'response' => [],
            'http_status' => 200,
        ];
    }
}
