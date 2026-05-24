<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteSettingFactory extends Factory
{
    protected $model = SiteSetting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => $this->faker->sentence(),
            'type' => 'text',
            'group' => 'general',
        ];
    }
}
