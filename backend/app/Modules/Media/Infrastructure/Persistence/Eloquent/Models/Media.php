<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Media extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'owner_user_id',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
