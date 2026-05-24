<?php

namespace Database\Factories;

use App\Models\LegalPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LegalPage>
 */
class LegalPageFactory extends Factory
{
    protected $model = LegalPage::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->paragraphs(3, true),
            'meta_title' => $this->faker->sentence(4),
            'meta_description' => $this->faker->sentence(10),
            'is_published' => true,
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn() => [
            'is_published' => false,
        ]);
    }
}
