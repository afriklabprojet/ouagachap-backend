<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'rater_id' => User::factory()->client(),
            'rated_id' => User::factory()->courier(),
            'type' => Rating::TYPE_CLIENT_TO_COURIER,
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
            'tags' => [],
            'is_visible' => true,
        ];
    }

    public function clientToCourier(): static
    {
        return $this->state(fn() => [
            'type' => Rating::TYPE_CLIENT_TO_COURIER,
        ]);
    }

    public function courierToClient(): static
    {
        return $this->state(fn() => [
            'type' => Rating::TYPE_COURIER_TO_CLIENT,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn() => [
            'is_visible' => false,
        ]);
    }
}
