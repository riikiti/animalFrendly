<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeders', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('owner_user_id');
            $table->foreign('owner_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();

            $table->ulid('verified_by')->nullable();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeders');
    }
};
