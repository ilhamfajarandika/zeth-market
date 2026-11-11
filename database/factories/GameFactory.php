<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'publisher' => fake()->company(),
            'requires_zone_id' => false,
            'user_id_label' => 'User ID',
            'user_id_regex' => '^\d{6,12}$',
            'is_active' => true,
            'is_popular' => false,
            'sort' => 0,
        ];
    }

    /**
     * Game dengan Zone/Server ID (contoh: Mobile Legends).
     */
    public function withZone(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_zone_id' => true,
            'zone_id_label' => 'Zone ID',
            'zone_id_regex' => '^\d{3,6}$',
        ]);
    }
}
