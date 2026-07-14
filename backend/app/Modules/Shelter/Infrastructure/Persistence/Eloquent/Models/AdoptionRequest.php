<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class AdoptionRequest extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'shelter_animal_id',
        'requester_user_id',
        'status',
        'message',
        'decided_at',
        'decided_by',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];
}
