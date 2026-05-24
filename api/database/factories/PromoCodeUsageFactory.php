<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromoCodeUsageFactory extends Factory
{
    protected $model = PromoCodeUsage::class;

    public function definition(): array
    {
        return [
            'promo_code_id' => PromoCode::factory(),
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'discount_applied' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
