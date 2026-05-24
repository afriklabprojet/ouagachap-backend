<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintMessageFactory extends Factory
{
    protected $model = ComplaintMessage::class;

    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'user_id' => User::factory(),
            'message' => $this->faker->paragraph(),
            'is_admin' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => true,
        ]);
    }
}
