<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
            'amount' => fake()->numberBetween(500, 5000),
            'method' => fake()->randomElement(PaymentMethod::cases())->value,
            'status' => PaymentStatus::PENDING->value,
            'phone_number' => '+22670' . fake()->numerify('######'),
            'provider_transaction_id' => null,
            'provider_response' => null,
            'paid_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::SUCCESS->value,
            'provider_transaction_id' => 'PROV-' . strtoupper(Str::random(10)),
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED->value,
            'provider_response' => json_encode(['error' => 'Payment declined']),
        ]);
    }

    public function orangeMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::ORANGE_MONEY->value,
        ]);
    }

    public function moovMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::MOOV_MONEY->value,
        ]);
    }
}
