<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'         => fake()->name(),
            'phone'        => fake()->unique()->numerify('7#######'),
            'email'        => fake()->unique()->safeEmail(),
            'firebase_uid' => 'firebase_' . Str::random(28),
            'role'         => UserRole::CLIENT,
            'status'       => UserStatus::ACTIVE,
            'is_available' => false,
            'avatar'       => null,
            'password'     => null,
            'fcm_token'    => null,
        ];
    }

    public function client(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::CLIENT,
        ]);
    }

    public function courier(): static
    {
        return $this->state(fn(array $attributes) => [
            'role'         => UserRole::COURIER,
            'is_available' => true,
            'kyc_status'   => KycStatus::APPROVED,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::ADMIN,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => UserStatus::PENDING,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => UserStatus::ACTIVE,
        ]);
    }
}
