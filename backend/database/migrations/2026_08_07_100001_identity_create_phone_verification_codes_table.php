<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_verification_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('phone', 32);
            // login | password_reset — один и тот же механизм обслуживает вход по коду
            // и восстановление пароля, но код от одного не подходит другому.
            $table->string('purpose', 32);
            // Сам код не храним: только хеш, как и пароль.
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verification_codes');
    }
};
