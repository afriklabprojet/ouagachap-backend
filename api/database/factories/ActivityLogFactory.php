<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'log_type' => $this->faker->randomElement([
                ActivityLog::TYPE_AUTH,
                ActivityLog::TYPE_ORDER,
                ActivityLog::TYPE_PAYMENT,
                ActivityLog::TYPE_ADMIN,
                ActivityLog::TYPE_SYSTEM,
            ]),
            'action' => $this->faker->randomElement([
                ActivityLog::ACTION_LOGIN,
                ActivityLog::ACTION_CREATE,
                ActivityLog::ACTION_UPDATE,
                ActivityLog::ACTION_DELETE,
            ]),
            'description' => $this->faker->sentence(),
            'user_id' => User::factory(),
            'subject_type' => 'App\\Models\\Order',
            'subject_id' => $this->faker->randomNumber(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
