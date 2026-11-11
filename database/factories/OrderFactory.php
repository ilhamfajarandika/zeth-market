<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(5, 500) * 1000;

        return [
            'invoice' => Order::generateInvoice(),
            'product_id' => Product::factory(),
            'game_id' => fn (array $attributes) => Product::find($attributes['product_id'])->game_id,
            'product_type' => fn (array $attributes) => Product::find($attributes['product_id'])->type,
            'product_name' => fn (array $attributes) => Product::find($attributes['product_id'])->name,
            'target_user_id' => (string) fake()->numberBetween(100000000, 999999999),
            'email' => fake()->safeEmail(),
            'whatsapp' => '08'.fake()->numerify('##########'),
            'price' => $price,
            'fee' => 0,
            'total' => $price,
            'status' => OrderStatus::Pending,
            'expired_at' => now()->addMinutes(15),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
