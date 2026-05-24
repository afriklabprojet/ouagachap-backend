<?php

namespace Database\Factories;

use App\Models\SupportChat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportChatFactory extends Factory
{
    protected $model = SupportChat::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'open',
            'subject' => $this->faker->sentence(3),
            'last_message_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'closed',
        ]);
    }
}
