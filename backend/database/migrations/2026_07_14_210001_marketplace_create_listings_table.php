<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('seller_id');
            $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();

            $table->ulid('pet_id');
            $table->foreign('pet_id')->references('id')->on('pets')->cascadeOnDelete();

            $table->bigInteger('price_amount');
            $table->char('currency', 3)->default('RUB');
            $table->enum('status', ['draft', 'published', 'reserved', 'sold', 'archived'])->default('draft');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
