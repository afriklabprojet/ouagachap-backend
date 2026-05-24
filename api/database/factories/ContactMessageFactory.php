<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'is_read' => false,
            'admin_reply' => null,
            'replied_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_read' => true,
        ]);
    }

    public function replied(): static
    {
        return $this->state(fn(array $attributes) => [
            'admin_reply' => fake()->paragraph(),
            'replied_at' => now(),
            'is_read' => true,
        ]);
    }
}
