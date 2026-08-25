<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Оформление: то, что покупатель оплачивает одним платежом. Своей доменной сущности у
 * него нет — вся логика живёт в заказах, а эта запись нужна, чтобы связать их между
 * собой и с платежом.
 */
final class ShopCheckoutModel extends Model
{
    use HasUlids;

    protected $table = 'shop_checkouts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'buyer_id', 'amount', 'currency'];

    protected $casts = ['amount' => 'integer'];
}
