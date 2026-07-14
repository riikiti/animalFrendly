<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Conversation extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'match_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
