<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Shelter extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'owner_user_id',
        'legal_name',
        'inn',
        'description',
        'verification_status',
        'verified_at',
        'verified_by',
        'address',
        'city',
        'latitude',
        'longitude',
        'phone',
        'email',
        'photo_url',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
