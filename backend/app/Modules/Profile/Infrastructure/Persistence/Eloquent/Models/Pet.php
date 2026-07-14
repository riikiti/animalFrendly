<?php

declare(strict_types=1);

namespace App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Pet extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'owner_id',
        'species_id',
        'breed_id',
        'name',
        'sex',
        'birthdate',
        'purpose',
        'description',
        'health_notes',
        'is_vaccinated',
        'status',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'is_vaccinated' => 'boolean',
    ];
}
