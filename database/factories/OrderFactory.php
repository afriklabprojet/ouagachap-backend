<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $totalPrice = fake()->numberBetween(500, 5000);
        $commissionRate = 0.15;
        $commissionAmount = $totalPrice * $commissionRate;

        return [
            'id' => Str::uuid(),
            'order_number' => 'OC-' . strtoupper(Str::random(8)),
            'client_id' => User::factory(),
            'courier_id' => null,
            'zone_id' => null,
            'status' => OrderStatus::PENDING,
            
            // Pickup
            'pickup_address' => fake()->address(),
            'pickup_latitude' => fake()->latitude(12.30, 12.45),
            'pickup_longitude' => fake()->longitude(-1.60, -1.45),
            'pickup_contact_name' => fake()->name(),
            'pickup_contact_phone' => '70' . fake()->numerify('######'),
            'pickup_instructions' => fake()->optional()->sentence(),
            
            // Dropoff
            'dropoff_address' => fake()->address(),
            'dropoff_latitude' => fake()->latitude(12.30, 12.45),
            'dropoff_longitude' => fake()->longitude(-1.60, -1.45),
            'dropoff_contact_name' => fake()->name(),
            'dropoff_contact_phone' => '70' . fake()->numerify('######'),
            'dropoff_instructions' => fake()->optional()->sentence(),
            
            // Package
            'package_description' => fake()->sentence(),
            'package_size' => fake()->randomElement(['small', 'medium', 'large']),
            
            // Pricing
            'distance_km' => fake()->randomFloat(2, 1, 15),
            'base_price' => 500,
            'distance_price' => $totalPrice - 500,
            'total_price' => $totalPrice,
            'commission_amount' => $commissionAmount,
            'courier_earnings' => $totalPrice - $commissionAmount,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PENDING,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::ASSIGNED,
            'courier_id' => User::factory()->courier(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DELIVERED,
            'courier_id' => User::factory()->courier(),
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
