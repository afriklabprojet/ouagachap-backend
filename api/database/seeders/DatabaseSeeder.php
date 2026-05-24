<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SiteSettingsSeeder::class,
            CourierAppSettingsSeeder::class,
            ConfigurationSettingsSeeder::class,
            LegalPageSeeder::class,
        ]);
    }
}
