<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Внутренняя лог-таблица (аудит переходов) — bigint identity допустим по конвенции,
        // см. docs/database/00-conventions.md.
        Schema::create('order_status_history', function (Blueprint $table): void {
            $table->id();

            $table->ulid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');

            // null = системный/крон-переход (например, авто-подтверждение).
            $table->ulid('actor_user_id')->nullable();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();

            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
