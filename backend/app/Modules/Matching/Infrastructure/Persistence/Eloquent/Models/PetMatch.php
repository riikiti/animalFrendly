<?php

declare(strict_types=1);

namespace App\Modules\Matching\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class PetMatch extends Model
{
    protected $table = 'matches';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'pet_a_id',
        'pet_b_id',
        'matched_at',
        'unmatched_at',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'unmatched_at' => 'datetime',
    ];
}
