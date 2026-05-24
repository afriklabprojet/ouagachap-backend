<?php

namespace Database\Factories;

use App\Models\AutoAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutoAlertFactory extends Factory
{
    protected $model = AutoAlert::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'trigger_type' => fake()->randomElement([
                'order_delayed',
                'courier_offline',
                'low_couriers',
                'high_pending_orders',
                'withdrawal_pending',
                'negative_rating',
            ]),
            'conditions' => ['threshold' => fake()->numberBetween(1, 10)],
            'actions' => ['notify_admin' => true],
            'is_active' => true,
            'cooldown_minutes' => fake()->randomElement([5, 10, 15, 30, 60]),
            'last_triggered_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function recentlyTriggered(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_triggered_at' => now()->subMinute(),
            'cooldown_minutes' => 30,
        ]);
    }

    public function cooldownExpired(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_triggered_at' => now()->subHours(2),
            'cooldown_minutes' => 30,
        ]);
    }
}
