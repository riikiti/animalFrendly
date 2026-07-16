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
            $table->ulid('shelter_id')->nullable()->after('adoption_request_id');
            $table->foreign('shelter_id')->references('id')->on('shelters')->cascadeOnDelete();

            $table->ulid('initiator_user_id')->nullable()->after('shelter_id');
            $table->foreign('initiator_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->ulid('shelter_animal_id')->nullable()->after('initiator_user_id');
            $table->foreign('shelter_animal_id')->references('id')->on('shelter_animals')->nullOnDelete();

            $table->unique(['shelter_id', 'initiator_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique(['shelter_id', 'initiator_user_id']);
            $table->dropForeign(['shelter_animal_id']);
            $table->dropForeign(['initiator_user_id']);
            $table->dropForeign(['shelter_id']);
            $table->dropColumn(['shelter_animal_id', 'initiator_user_id', 'shelter_id']);
        });
    }
};
