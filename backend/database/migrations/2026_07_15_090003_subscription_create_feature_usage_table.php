<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_usage', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->ulid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->string('feature_key');
            $table->date('period_start');
            $table->integer('used_count')->default(0);

            $table->unique(['user_id', 'feature_key', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usage');
    }
};
