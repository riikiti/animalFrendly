<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShopProduct extends Model
{
    use HasUlids;

    protected $table = 'shop_products';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'seller_id',
        'category_id',
        'title',
        'description',
        'price_amount',
        'currency',
        'stock',
        'status',
        'photo_url',
    ];

    protected $casts = [
        'price_amount' => 'integer',
        'stock' => 'integer',
    ];

    /**
     * @return BelongsTo<ShopCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }
}
