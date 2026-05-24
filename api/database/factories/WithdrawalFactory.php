<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        return [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $this->faker->randomFloat(2, 500, 50000),
            'status' => 'pending',
            'payment_method' => $this->faker->randomElement(['mobile_money', 'bank_transfer']),
            'payment_phone' => $this->faker->phoneNumber(),
            'payment_provider' => $this->faker->randomElement(['orange_money', 'moov_money', null]),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'transaction_reference' => 'TXN-' . $this->faker->uuid(),
            'completed_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
