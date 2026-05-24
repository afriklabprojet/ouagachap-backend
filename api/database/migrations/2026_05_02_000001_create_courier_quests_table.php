<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_quests', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Identifiant unique ex: first_delivery, ten_deliveries');
            $table->string('title');
            $table->text('description');
            $table->string('icon')->default('🏆');
            $table->string('quest_type')->comment('delivery_count | revenue_target | streak_days | rating_avg');
            $table->integer('target_value')->comment('Valeur cible à atteindre');
            $table->integer('bonus_xof')->default(0)->comment('Bonus en FCFA attribué à la completion');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Quêtes de livraisons (delivery_count)
        DB::table('courier_quests')->insert([
            [
                'key'          => 'first_delivery',
                'title'        => 'Première livraison',
                'description'  => 'Effectuez votre toute première livraison',
                'icon'         => '🚀',
                'quest_type'   => 'delivery_count',
                'target_value' => 1,
                'bonus_xof'    => 500,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'ten_deliveries',
                'title'        => '10 Livraisons',
                'description'  => 'Atteignez 10 livraisons réussies',
                'icon'         => '📦',
                'quest_type'   => 'delivery_count',
                'target_value' => 10,
                'bonus_xof'    => 1000,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'fifty_deliveries',
                'title'        => '50 Livraisons',
                'description'  => 'Atteignez 50 livraisons réussies',
                'icon'         => '⭐',
                'quest_type'   => 'delivery_count',
                'target_value' => 50,
                'bonus_xof'    => 2500,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'hundred_deliveries',
                'title'        => '100 Livraisons',
                'description'  => 'Atteignez 100 livraisons réussies',
                'icon'         => '💯',
                'quest_type'   => 'delivery_count',
                'target_value' => 100,
                'bonus_xof'    => 5000,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            // Quêtes revenue
            [
                'key'          => 'first_10k_xof',
                'title'        => '10 000 FCFA gagnés',
                'description'  => 'Cumulez 10 000 FCFA de revenus',
                'icon'         => '💰',
                'quest_type'   => 'revenue_target',
                'target_value' => 10000,
                'bonus_xof'    => 500,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            // Quêtes streak
            [
                'key'          => 'streak_3_days',
                'title'        => 'Actif 3 jours de suite',
                'description'  => 'Effectuez au moins 1 livraison par jour pendant 3 jours consécutifs',
                'icon'         => '🔥',
                'quest_type'   => 'streak_days',
                'target_value' => 3,
                'bonus_xof'    => 750,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_quests');
    }
};
