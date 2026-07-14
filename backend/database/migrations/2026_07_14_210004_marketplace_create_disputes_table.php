<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('order_id')->unique();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->ulid('opened_by');
            $table->foreign('opened_by')->references('id')->on('users')->cascadeOnDelete();

            $table->text('reason');
            $table->enum('resolution', ['seller_wins', 'buyer_wins'])->nullable();

            $table->ulid('resolved_by')->nullable();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
