<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeds', function (Blueprint $table): void {
            $table->increments('id');
            // unsignedSmallInteger, не foreignId(): species.id — smallIncrements, а не bigint.
            $table->unsignedSmallInteger('species_id');
            $table->foreign('species_id')->references('id')->on('species')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name_ru');
            $table->jsonb('attributes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unique(['species_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeds');
    }
};
