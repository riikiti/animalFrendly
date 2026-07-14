<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('phone')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password_hash');
            $table->enum('account_type', ['owner', 'breeder', 'shelter', 'admin', 'moderator'])->default('owner');
            $table->enum('status', ['active', 'blocked', 'pending_verification'])->default('active');
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('personal_data_consent_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
