<?php

namespace Database\Factories;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    public function definition(): array
    {
        return [
            'support_chat_id' => SupportChat::factory(),
            'user_id' => User::factory(),
            'message' => $this->faker->paragraph(),
            'is_admin' => false,
            'is_read' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_read' => true,
        ]);
    }
}
