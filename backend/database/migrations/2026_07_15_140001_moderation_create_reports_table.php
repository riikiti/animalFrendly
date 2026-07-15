<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('reporter_id');
            $table->foreign('reporter_id')->references('id')->on('users')->cascadeOnDelete();

            $table->string('target_type');
            $table->string('target_id');
            $table->string('reason');
            $table->text('comment')->nullable();
            $table->string('status')->default('pending');

            $table->ulid('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
