<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Listing extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'seller_id',
        'pet_id',
        'price_amount',
        'currency',
        'status',
    ];
}
