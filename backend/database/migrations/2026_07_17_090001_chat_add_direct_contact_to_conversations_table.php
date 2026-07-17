<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->ulid('recipient_user_id')->nullable()->after('shelter_animal_id');
            $table->foreign('recipient_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['recipient_user_id', 'initiator_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique(['recipient_user_id', 'initiator_user_id']);
            $table->dropForeign(['recipient_user_id']);
            $table->dropColumn('recipient_user_id');
        });
    }
};
