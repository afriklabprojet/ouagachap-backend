<?php

namespace Database\Factories;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Zone>
 */
class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    public function definition(): array
    {
        return [
            'name'            => fake()->city() . ' Zone',
            'code'            => strtoupper(fake()->unique()->lexify('??')),
            'description'     => fake()->sentence(),
            'base_price'      => 500.00,
            'price_per_km'    => 200.00,
            'commission_rate' => 0.15,
            'is_active'       => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
