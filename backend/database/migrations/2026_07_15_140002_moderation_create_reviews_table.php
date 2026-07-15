<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            // Ровно одно из двух заполнено — проверяется в Review::create(), не на уровне БД
            // (тот же уровень строгости, что у Conversation.match_id/adoption_request_id).
            $table->ulid('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->ulid('adoption_request_id')->nullable();
            $table->foreign('adoption_request_id')->references('id')->on('adoption_requests')->cascadeOnDelete();

            $table->ulid('author_id');
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();

            $table->ulid('target_user_id');
            $table->foreign('target_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->smallInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['author_id', 'order_id']);
            $table->unique(['author_id', 'adoption_request_id']);
            $table->index(['target_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
