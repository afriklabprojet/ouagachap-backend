<?php

namespace Database\Factories;

use App\Models\Geofence;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeofenceFactory extends Factory
{
    protected $model = Geofence::class;

    public function definition(): array
    {
        return [
            'name' => fake()->city() . ' Zone',
            'coordinates' => [
                ['lat' => 12.35, 'lng' => -1.52],
                ['lat' => 12.36, 'lng' => -1.51],
                ['lat' => 12.35, 'lng' => -1.50],
                ['lat' => 12.34, 'lng' => -1.51],
            ],
            'type' => fake()->randomElement(['allowed', 'restricted', 'surge']),
            'surge_multiplier' => fake()->randomFloat(2, 1.0, 2.5),
            'is_active' => true,
        ];
    }

    public function allowed(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'allowed',
        ]);
    }

    public function restricted(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'restricted',
        ]);
    }

    public function surge(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'surge',
            'surge_multiplier' => 1.5,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
