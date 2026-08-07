<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('shop_categories')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Деньги в копейках целым числом — как в остальных модулях (см. Shared\Money).
            $table->unsignedBigInteger('price_amount');
            $table->string('currency', 3)->default('RUB');
            $table->unsignedInteger('stock')->default(0);
            $table->string('status', 16)->default('draft');
            $table->string('photo_url')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('seller_id');
        });

        Schema::create('shop_cart_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            // Повторное добавление того же товара увеличивает количество, а не плодит строки.
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_cart_items');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_categories');
    }
};
