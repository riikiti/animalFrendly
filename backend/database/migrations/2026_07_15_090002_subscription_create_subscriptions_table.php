<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedSmallInteger('plan_id');
            $table->foreign('plan_id')->references('id')->on('subscription_plans')->restrictOnDelete();

            $table->enum('status', ['pending_payment', 'active', 'canceled', 'expired', 'past_due'])
                ->default('pending_payment');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('canceled_at')->nullable();
            $table->string('yookassa_payment_method_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
