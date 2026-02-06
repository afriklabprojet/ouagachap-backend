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

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => $this->faker->randomFloat(2, 0, 100000),
            'pending_balance' => $this->faker->randomFloat(2, 0, 10000),
            'total_earned' => $this->faker->randomFloat(2, 0, 500000),
            'total_withdrawn' => $this->faker->randomFloat(2, 0, 200000),
        ];
    }

    /**
     * Empty wallet state
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0,
            'pending_balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
        ]);
    }

    /**
     * Wallet with a specific balance
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }

    /**
     * Wallet with pending withdrawal
     */
    public function withPendingBalance(float $pending): static
    {
        return $this->state(fn (array $attributes) => [
            'pending_balance' => $pending,
        ]);
    }
}
