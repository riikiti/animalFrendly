<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelter_animals', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('shelter_id');
            $table->foreign('shelter_id')->references('id')->on('shelters')->cascadeOnDelete();

            $table->ulid('pet_id');
            $table->foreign('pet_id')->references('id')->on('pets')->cascadeOnDelete();

            $table->enum('status', ['available', 'reserved', 'adopted', 'removed'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelter_animals');
    }
};
