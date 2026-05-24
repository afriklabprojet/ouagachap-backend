<?php

namespace Database\Factories;

use App\Models\OrderMessage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderMessage>
 */
class OrderMessageFactory extends Factory
{
    protected $model = OrderMessage::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'sender_id' => User::factory(),
            'sender_type' => $this->faker->randomElement(['client', 'courier']),
            'message' => $this->faker->sentence(),
            'image_url' => null,
            'is_read' => false,
        ];
    }

    public function fromClient(): static
    {
        return $this->state(fn() => [
            'sender_type' => 'client',
        ]);
    }

    public function fromCourier(): static
    {
        return $this->state(fn() => [
            'sender_type' => 'courier',
        ]);
    }

    public function read(): static
    {
        return $this->state(fn() => [
            'is_read' => true,
        ]);
    }
}
