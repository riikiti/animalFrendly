<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();

            // unsignedSmallInteger/unsignedInteger, не foreignId(): species.id и breeds.id
            // не bigint — см. database/migrations/*_catalog_create_*_table.php.
            $table->unsignedSmallInteger('species_id');
            $table->foreign('species_id')->references('id')->on('species');

            $table->unsignedInteger('breed_id')->nullable();
            $table->foreign('breed_id')->references('id')->on('breeds')->nullOnDelete();

            $table->string('name');
            $table->enum('sex', ['male', 'female']);
            $table->date('birthdate')->nullable();
            $table->enum('purpose', ['social', 'breeding', 'for_sale', 'shelter']);
            $table->text('description')->nullable();
            $table->text('health_notes')->nullable();
            $table->boolean('is_vaccinated')->default(false);
            $table->enum('status', ['active', 'hidden', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
