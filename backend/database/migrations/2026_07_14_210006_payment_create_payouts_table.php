<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('order_id')->unique();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->ulid('seller_id');
            $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();

            $table->bigInteger('amount');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed'])->default('pending');
            $table->string('yookassa_payout_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
