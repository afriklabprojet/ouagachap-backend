<?php

namespace Database\Factories;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PromoCodeFactory extends Factory
{
    protected $model = PromoCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['percentage', 'fixed', 'free_delivery']),
            'value' => fake()->randomFloat(2, 5, 50),
            'min_order_amount' => null,
            'max_discount' => null,
            'max_uses' => null,
            'max_uses_per_user' => 1,
            'current_uses' => 0,
            'is_active' => true,
            'first_order_only' => false,
            'applicable_zones' => null,
            'starts_at' => null,
            'expires_at' => null,
        ];
    }

    public function percentage(float $value = 10): static
    {
        return $this->state(fn() => [
            'type' => 'percentage',
            'value' => $value,
        ]);
    }

    public function fixed(float $value = 500): static
    {
        return $this->state(fn() => [
            'type' => 'fixed',
            'value' => $value,
        ]);
    }

    public function freeDelivery(): static
    {
        return $this->state(fn() => [
            'type' => 'free_delivery',
            'value' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }
}
