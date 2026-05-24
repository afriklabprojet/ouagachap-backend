<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question'  => $this->faker->sentence() . '?',
            'answer'    => $this->faker->paragraph(),
            'category'  => $this->faker->randomElement(['general', 'payment', 'delivery', 'account']),
            'target'    => 'all',
            'is_active' => true,
            'order'     => $this->faker->numberBetween(0, 100),
            'views'     => $this->faker->numberBetween(0, 500),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
