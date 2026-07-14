<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->ulid('conversation_id');
            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();

            $table->ulid('sender_id');
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();

            $table->text('body');
            // attachment_media_id добавится вместе с модулем Media.
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
