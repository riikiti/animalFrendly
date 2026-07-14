<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('listing_id');
            $table->foreign('listing_id')->references('id')->on('listings')->cascadeOnDelete();

            $table->ulid('buyer_id');
            $table->foreign('buyer_id')->references('id')->on('users')->cascadeOnDelete();

            // Денормализовано из listing на момент покупки — продавец сделки не должен
            // меняться, даже если продавец в будущем отредактирует листинг.
            $table->ulid('seller_id');
            $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();

            $table->bigInteger('amount');
            $table->char('currency', 3)->default('RUB');
            $table->bigInteger('commission_amount')->nullable();
            $table->bigInteger('payout_amount')->nullable();

            $table->enum('status', [
                'pending_payment', 'paid_escrow', 'completed', 'disputed', 'refunded', 'cancelled',
            ])->default('pending_payment');

            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('seller_confirmed_at')->nullable();
            $table->timestamp('escrow_hold_until')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
