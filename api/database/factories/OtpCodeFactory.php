<?php

namespace Database\Factories;

use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    public function definition(): array
    {
        return [
            'phone' => '+226' . $this->faker->numerify('########'),
            'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
            'max_attempts' => 3,
            'purpose' => $this->faker->randomElement([
                OtpCode::PURPOSE_LOGIN,
                OtpCode::PURPOSE_REGISTER,
                OtpCode::PURPOSE_PASSWORD_RESET,
                OtpCode::PURPOSE_PHONE_VERIFICATION,
            ]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }

    public function used(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_used' => true,
        ]);
    }

    public function forPhone(string $phone): self
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $phone,
        ]);
    }
}
