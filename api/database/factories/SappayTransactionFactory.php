<?php

namespace Database\Factories;

use App\Models\SappayTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SappayTransactionFactory extends Factory
{
    protected $model = SappayTransaction::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory()->client(),
            'invoice_id'     => null,
            'reference'      => 'PAY-' . strtoupper(Str::random(10)),
            'type'           => 'wallet_recharge',
            'payment_method' => $this->faker->randomElement(['wave', 'orange', 'moov']),
            'amount'         => $this->faker->randomFloat(2, 500, 50000),
            'currency'       => 'XOF',
            'status'         => 'pending',
            'metadata'       => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function successful(): static
    {
        return $this->state([
            'status'      => 'success',
            'executed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'error']);
    }

    public function walletRecharge(): static
    {
        return $this->state(['type' => 'wallet_recharge']);
    }

    public function orderPayment(): static
    {
        return $this->state(['type' => 'order_payment']);
    }
}
