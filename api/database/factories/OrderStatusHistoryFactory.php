<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => OrderStatus::ASSIGNED,
            'previous_status' => OrderStatus::PENDING,
            'changed_by' => User::factory(),
            'note' => fake()->optional()->sentence(),
            'latitude' => fake()->latitude(12.30, 12.45),
            'longitude' => fake()->longitude(-1.60, -1.45),
        ];
    }
}
