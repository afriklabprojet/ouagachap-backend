<?php

namespace Database\Factories;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Centre-ville', 'Ouaga 2000', 'Zone 1', 'Tanghin', 'Dassasgho']),
            'code' => strtoupper(Str::random(4)),
            'description' => fake()->optional()->sentence(),
            'base_price' => fake()->randomElement([500, 750, 1000]),
            'price_per_km' => fake()->randomElement([150, 200, 250]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
