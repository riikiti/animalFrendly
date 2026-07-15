<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->smallIncrements('id');

            $table->string('slug')->unique();
            $table->string('name_ru');

            $table->bigInteger('price_amount');
            $table->char('currency', 3)->default('RUB');
            $table->enum('period', ['month', 'year']);

            $table->jsonb('features');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
