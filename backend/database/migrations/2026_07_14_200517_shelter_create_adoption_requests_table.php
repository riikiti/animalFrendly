<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoption_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('shelter_animal_id');
            $table->foreign('shelter_animal_id')->references('id')->on('shelter_animals')->cascadeOnDelete();

            $table->ulid('requester_user_id');
            $table->foreign('requester_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->ulid('decided_by')->nullable();
            $table->foreign('decided_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoption_requests');
    }
};
