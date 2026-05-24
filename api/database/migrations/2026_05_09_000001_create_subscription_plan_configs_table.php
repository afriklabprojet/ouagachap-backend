<?php

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan_configs', function (Blueprint $table) {
            $table->id();
            $table->string('plan')->unique()->comment('basic | premium');
            $table->string('label')->comment('Nom affiché dans l\'app');
            $table->integer('price_xof')->comment('Tarif mensuel en XOF');
            $table->integer('discount_xof')->comment('Remise appliquée par livraison (XOF)');
            $table->boolean('priority_dispatch')->default(false)->comment('Dispatch prioritaire');
            $table->boolean('is_active')->default(true)->comment('Plan disponible à la souscription');
            $table->text('description')->nullable()->comment('Description affichée dans l\'app');
            $table->timestamps();
        });

        // Seed des valeurs par défaut
        DB::table('subscription_plan_configs')->insert([
            [
                'plan'              => SubscriptionPlan::BASIC->value,
                'label'             => 'CHAP Pass Basic',
                'price_xof'         => 3500,
                'discount_xof'      => 150,
                'priority_dispatch' => false,
                'is_active'         => true,
                'description'       => 'Économisez 150 FCFA sur chaque livraison.',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'plan'              => SubscriptionPlan::PREMIUM->value,
                'label'             => 'CHAP Pass Premium',
                'price_xof'         => 7000,
                'discount_xof'      => 300,
                'priority_dispatch' => true,
                'is_active'         => true,
                'description'       => 'Économisez 300 FCFA par livraison + dispatch prioritaire.',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_configs');
    }
};
