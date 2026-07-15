<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Report extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'reporter_id',
        'target_type',
        'target_id',
        'reason',
        'comment',
        'status',
        'reviewed_by',
        'reviewed_at',
        'created_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
