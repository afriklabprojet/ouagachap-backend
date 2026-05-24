<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'client_id'              => User::factory()->client(),
            'courier_id'             => null,
            'recipient_user_id'      => null,
            'zone_id'                => null,
            'status'                 => OrderStatus::PENDING,

            // Pickup
            'pickup_address'         => fake()->address(),
            'pickup_latitude'        => fake()->latitude(12.30, 12.45),
            'pickup_longitude'       => fake()->longitude(-1.60, -1.45),
            'pickup_contact_name'    => fake()->name(),
            'pickup_contact_phone'   => fake()->numerify('7########'),
            'pickup_instructions'    => null,

            // Dropoff
            'dropoff_address'        => fake()->address(),
            'dropoff_latitude'       => fake()->latitude(12.30, 12.45),
            'dropoff_longitude'      => fake()->longitude(-1.60, -1.45),
            'dropoff_contact_name'   => fake()->name(),
            'dropoff_contact_phone'  => fake()->numerify('7########'),
            'dropoff_instructions'   => null,

            // Destinataire
            'recipient_confirmation_code' => fake()->numerify('######'),
            'recipient_confirmed'    => false,

            // Colis
            'package_description'   => fake()->sentence(3),
            'package_size'          => fake()->randomElement(['small', 'medium', 'large']),

            // Tarification
            'distance_km'           => fake()->randomFloat(2, 0.5, 15),
            'base_price'            => 500.00,
            'distance_price'        => fake()->randomFloat(2, 100, 3000),
            'total_price'           => fake()->randomFloat(2, 600, 4000),
            'commission_amount'     => fake()->randomFloat(2, 90, 600),
            'courier_earnings'      => fake()->randomFloat(2, 510, 3400),

            // Timestamps de statut
            'assigned_at'           => null,
            'picked_up_at'          => null,
            'delivered_at'          => null,
            'cancelled_at'          => null,
            'cancellation_reason'   => null,

            // Notations
            'client_rating'         => null,
            'client_review'         => null,
            'courier_rating'        => null,
            'courier_review'        => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'        => OrderStatus::PENDING,
            'courier_id'    => null,
            'assigned_at'   => null,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'      => OrderStatus::ASSIGNED,
            'courier_id'  => User::factory()->courier(),
            'assigned_at' => now(),
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => OrderStatus::IN_TRANSIT,
            'courier_id'   => User::factory()->courier(),
            'assigned_at'  => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(10),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => OrderStatus::DELIVERED,
            'courier_id'   => User::factory()->courier(),
            'assigned_at'  => now()->subHour(),
            'picked_up_at' => now()->subMinutes(40),
            'delivered_at' => now()->subMinutes(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'              => OrderStatus::CANCELLED,
            'cancelled_at'        => now(),
            'cancellation_reason' => 'Annulé par le client',
        ]);
    }
}
