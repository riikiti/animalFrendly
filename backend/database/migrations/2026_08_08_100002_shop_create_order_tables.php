<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('buyer_id')->constrained('users')->cascadeOnDelete();
            // Заказ всегда к одному продавцу: эскроу и выплата считаются на него.
            $table->foreignUlid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending_payment');

            $table->unsignedBigInteger('items_amount');
            $table->unsignedBigInteger('delivery_amount');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('commission_amount')->nullable();
            $table->unsignedBigInteger('payout_amount')->nullable();
            $table->string('currency', 3)->default('RUB');

            $table->string('delivery_method', 32);
            $table->string('delivery_address')->nullable();

            $table->timestamp('escrow_hold_until')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('seller_confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });

        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('shop_products')->restrictOnDelete();
            // Название и цена копируются в заказ: продавец может потом их поменять,
            // а в уже оформленном заказе должно остаться то, что покупали.
            $table->string('title');
            $table->unsignedBigInteger('price_amount');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
    }
};
