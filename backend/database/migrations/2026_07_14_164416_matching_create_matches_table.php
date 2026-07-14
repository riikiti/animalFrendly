<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('pet_a_id');
            $table->foreign('pet_a_id')->references('id')->on('pets')->cascadeOnDelete();

            $table->ulid('pet_b_id');
            $table->foreign('pet_b_id')->references('id')->on('pets')->cascadeOnDelete();

            $table->timestamp('matched_at');
            $table->timestamp('unmatched_at')->nullable();

            $table->unique(['pet_a_id', 'pet_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
