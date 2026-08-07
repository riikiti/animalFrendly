<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ShopCartItem extends Model
{
    use HasUlids;

    protected $table = 'shop_cart_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];
}
