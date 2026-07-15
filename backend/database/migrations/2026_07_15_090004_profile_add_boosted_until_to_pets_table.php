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
            $table->timestamp('boosted_until')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table): void {
            $table->dropColumn('boosted_until');
        });
    }
};
