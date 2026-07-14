<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('slug')->unique();
            $table->string('name_ru');
            // Ссылка на media.id добавится FK-констрейнтом, когда появится модуль Media
            // (см. docs/database/07-notification-media-moderation.md).
            $table->ulid('icon_media_id')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};
