<?php

namespace Database\Factories;

use App\Models\TrafficIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrafficIncident>
 */
class TrafficIncidentFactory extends Factory
{
    protected $model = TrafficIncident::class;

    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'type' => $this->faker->randomElement(['congestion', 'accident', 'road_work', 'road_closed', 'police', 'hazard']),
            'severity' => $this->faker->randomElement(['low', 'moderate', 'high', 'severe']),
            'latitude' => $this->faker->latitude(12.30, 12.40),
            'longitude' => $this->faker->longitude(-1.58, -1.48),
            'address' => $this->faker->address(),
            'description' => $this->faker->sentence(),
            'confirmations' => 1,
            'is_active' => true,
            'expires_at' => now()->addHours(2),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
            'resolved_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function severe(): static
    {
        return $this->state(fn() => [
            'severity' => 'severe',
        ]);
    }
}
