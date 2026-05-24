<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'transaction_id'          => 'TXN-' . strtoupper(Str::random(8)),
            'order_id'                => Order::factory(),
            'user_id'                 => User::factory()->client(),
            'amount'                  => fake()->randomFloat(2, 500, 10000),
            'method'                  => fake()->randomElement(array_column(PaymentMethod::cases(), 'value')),
            'status'                  => 'pending',
            'payment_type'            => 'order_payment',
            'phone_number'            => fake()->numerify('+22670######'),
            'provider_transaction_id' => null,
            'provider_response'       => null,
            'paid_at'                 => null,
            'failed_at'               => null,
            'failure_reason'          => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'    => 'pending',
            'paid_at'   => null,
            'failed_at' => null,
        ]);
    }

    public function success(): static
    {
        return $this->successful();
    }

    public function successful(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'                  => 'success',
            'provider_transaction_id' => 'prov_' . Str::random(16),
            'paid_at'                 => now(),
            'failed_at'               => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'         => 'failed',
            'paid_at'        => null,
            'failed_at'      => now(),
            'failure_reason' => 'Solde insuffisant',
        ]);
    }

    public function orangeMoney(): static
    {
        return $this->state(fn(array $attributes) => [
            'method' => PaymentMethod::ORANGE_MONEY->value,
        ]);
    }

    public function wave(): static
    {
        return $this->state(fn(array $attributes) => [
            'method' => PaymentMethod::WAVE->value,
        ]);
    }
}
