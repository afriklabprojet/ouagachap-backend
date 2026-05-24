<?php

namespace Database\Factories;

use App\Models\WalletTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_id' => 'RECH-' . strtoupper($this->faker->bothify('????????')),
            'amount' => $this->faker->randomElement([500, 1000, 2000, 5000, 10000]),
            'type' => 'recharge',
            'method' => $this->faker->randomElement(['orange_money', 'moov_money']),
            'phone_number' => '7' . $this->faker->numerify('########'),
            'status' => 'pending',
        ];
    }

    public function success(): static
    {
        return $this->state(fn() => [
            'status' => 'success',
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn() => [
            'status' => 'failed',
            'failure_reason' => 'Insufficient balance',
        ]);
    }
}
