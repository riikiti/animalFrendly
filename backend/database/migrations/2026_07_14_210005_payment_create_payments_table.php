<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            // Полиморфная связь: payable_type='order' (сейчас) либо 'subscription' (позже,
            // см. docs/database/06-subscription.md) — без Eloquent morphTo, просто строка +
            // ulid, т.к. модуль Payment не должен импортировать Eloquent-модели других модулей.
            $table->string('payable_type');
            $table->ulid('payable_id');
            $table->index(['payable_type', 'payable_id']);

            $table->string('yookassa_payment_id')->nullable()->unique();
            $table->string('idempotency_key')->unique();

            $table->bigInteger('amount');
            $table->char('currency', 3)->default('RUB');

            $table->enum('status', ['pending', 'waiting_for_capture', 'succeeded', 'canceled', 'refunded'])
                ->default('pending');

            $table->jsonb('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
