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
        'adoption_request_id',
        'shelter_id',
        'initiator_user_id',
        'shelter_animal_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
