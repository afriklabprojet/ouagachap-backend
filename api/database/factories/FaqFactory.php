<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => $this->faker->randomElement(['general', 'paiement', 'livraison', 'compte']),
            'question' => $this->faker->sentence() . ' ?',
            'answer' => $this->faker->paragraph(),
            'order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'views' => $this->faker->numberBetween(0, 1000),
        ];
    }

    /**
     * Inactive FAQ
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
