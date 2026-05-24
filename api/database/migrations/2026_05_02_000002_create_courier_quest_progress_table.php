<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_quest_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quest_id')->constrained('courier_quests')->cascadeOnDelete();
            $table->integer('current_value')->default(0);
            $table->boolean('completed')->default(false);
            $table->boolean('reward_claimed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['courier_id', 'quest_id']);
            $table->index(['courier_id', 'completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_quest_progress');
    }
};
