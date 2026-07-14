<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique('conversations_match_id_unique');
            $table->ulid('match_id')->nullable()->change();

            $table->ulid('adoption_request_id')->nullable()->after('match_id');
            $table->foreign('adoption_request_id')->references('id')->on('adoption_requests')->cascadeOnDelete();
            $table->unique('adoption_request_id');
        });

        // Ровно один из двух источников беседы должен быть заполнен —
        // см. docs/database/03-matching-chat.md. CHECK-констрейнт через ALTER TABLE —
        // синтаксис, специфичный для Postgres (прод/дев); SQLite (Feature-тесты, см.
        // docs/rules/05-testing.md) не поддерживает добавление CHECK через ALTER TABLE —
        // там правило проверяется только на уровне приложения (Conversation::createFor*).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE conversations ADD CONSTRAINT conversations_exactly_one_source '.
                'CHECK ((match_id IS NOT NULL)::int + (adoption_request_id IS NOT NULL)::int = 1)',
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE conversations DROP CONSTRAINT conversations_exactly_one_source');
        }

        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropForeign(['adoption_request_id']);
            $table->dropUnique(['adoption_request_id']);
            $table->dropColumn('adoption_request_id');

            $table->ulid('match_id')->nullable(false)->change();
            $table->unique('match_id');
        });
    }
};
