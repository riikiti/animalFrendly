<?php

declare(strict_types=1);

namespace App\Modules\Matching\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Swipe extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'swiper_pet_id',
        'target_pet_id',
        'action',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
