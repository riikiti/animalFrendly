<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelters', function (Blueprint $table): void {
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('photo_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shelters', function (Blueprint $table): void {
            $table->dropColumn(['address', 'city', 'latitude', 'longitude', 'phone', 'email', 'photo_url']);
        });
    }
};
