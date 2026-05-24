<?php

namespace Database\Factories;

use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavedAddress>
 */
class SavedAddressFactory extends Factory
{
    protected $model = SavedAddress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Maison', 'Bureau', 'Chez Maman', 'Marché', 'École']),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(12.2, 12.5),
            'longitude' => fake()->longitude(-1.6, -1.4),
            'contact_name' => fake()->optional()->name(),
            'contact_phone' => fake()->optional()->phoneNumber(),
            'instructions' => fake()->optional()->sentence(),
            'is_default' => false,
            'type' => fake()->randomElement(['home', 'work', 'other']),
        ];
    }

    public function home(): static
    {
        return $this->state(fn() => ['type' => 'home', 'label' => 'Maison']);
    }

    public function work(): static
    {
        return $this->state(fn() => ['type' => 'work', 'label' => 'Bureau']);
    }

    public function default(): static
    {
        return $this->state(fn() => ['is_default' => true]);
    }
}
