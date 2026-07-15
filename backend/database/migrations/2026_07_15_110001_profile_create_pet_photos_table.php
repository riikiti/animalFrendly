<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_photos', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('pet_id');
            $table->foreign('pet_id')->references('id')->on('pets')->cascadeOnDelete();

            $table->ulid('media_id');
            $table->foreign('media_id')->references('id')->on('media')->cascadeOnDelete();

            $table->string('url');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pet_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_photos');
    }
};
