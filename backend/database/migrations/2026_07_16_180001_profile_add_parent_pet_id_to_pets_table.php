<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table): void {
            $table->ulid('parent_pet_id')->nullable()->after('breed_id');
            $table->foreign('parent_pet_id')->references('id')->on('pets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table): void {
            $table->dropForeign(['parent_pet_id']);
            $table->dropColumn('parent_pet_id');
        });
    }
};
