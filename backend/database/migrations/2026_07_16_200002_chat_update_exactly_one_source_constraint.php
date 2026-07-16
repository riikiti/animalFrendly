<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE conversations DROP CONSTRAINT conversations_exactly_one_source');
        DB::statement(
            'ALTER TABLE conversations ADD CONSTRAINT conversations_exactly_one_source '.
            'CHECK ((match_id IS NOT NULL)::int + (adoption_request_id IS NOT NULL)::int + (shelter_id IS NOT NULL)::int = 1)',
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE conversations DROP CONSTRAINT conversations_exactly_one_source');
        DB::statement(
            'ALTER TABLE conversations ADD CONSTRAINT conversations_exactly_one_source '.
            'CHECK ((match_id IS NOT NULL)::int + (adoption_request_id IS NOT NULL)::int = 1)',
        );
    }
};
