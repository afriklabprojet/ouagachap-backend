<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'balance'         => fake()->randomFloat(2, 0, 50000),
            'pending_balance' => 0.00,
            'total_earned'    => fake()->randomFloat(2, 0, 100000),
            'total_withdrawn' => fake()->randomFloat(2, 0, 50000),
        ];
    }

    public function empty(): static
    {
        return $this->state(fn(array $attributes) => [
            'balance'         => 0.00,
            'pending_balance' => 0.00,
            'total_earned'    => 0.00,
            'total_withdrawn' => 0.00,
        ]);
    }

    public function withBalance(float $amount): static
    {
        return $this->state(fn(array $attributes) => [
            'balance' => $amount,
        ]);
    }
}
