<?php

namespace Database\Factories;

use App\Models\Geofence;
use App\Models\GeofenceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeofenceLogFactory extends Factory
{
    protected $model = GeofenceLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'geofence_id' => Geofence::factory(),
            'event' => fake()->randomElement(['entered', 'exited']),
            'latitude' => fake()->latitude(12.30, 12.45),
            'longitude' => fake()->longitude(-1.60, -1.45),
        ];
    }

    public function entered(): static
    {
        return $this->state(fn(array $attributes) => [
            'event' => 'entered',
        ]);
    }

    public function exited(): static
    {
        return $this->state(fn(array $attributes) => [
            'event' => 'exited',
        ]);
    }
}
