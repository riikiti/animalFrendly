<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swipes', function (Blueprint $table): void {
            $table->id();

            $table->ulid('swiper_pet_id');
            $table->foreign('swiper_pet_id')->references('id')->on('pets')->cascadeOnDelete();

            $table->ulid('target_pet_id');
            $table->foreign('target_pet_id')->references('id')->on('pets')->cascadeOnDelete();

            $table->enum('action', ['like', 'dislike', 'super_like']);
            $table->timestamp('created_at');

            $table->unique(['swiper_pet_id', 'target_pet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swipes');
    }
};
