<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ShopCategory extends Model
{
    use HasUlids;

    protected $table = 'shop_categories';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['slug', 'name', 'position'];

    protected $casts = ['position' => 'integer'];
}
