<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['delivery_issue', 'payment_issue', 'courier_behavior', 'app_bug', 'other']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'open',
            'subject' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'resolved',
            'resolution' => $this->faker->paragraph(),
            'resolved_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'closed',
        ]);
    }
}
