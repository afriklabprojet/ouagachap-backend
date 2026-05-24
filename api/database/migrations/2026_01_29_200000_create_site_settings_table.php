<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('text'); // text, textarea, richtext, number, image, boolean, json
            $table->string('group')->default('general'); // general, hero, features, pricing, testimonials, contact, social, seo
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
