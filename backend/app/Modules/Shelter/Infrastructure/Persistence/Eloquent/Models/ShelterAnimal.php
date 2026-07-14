<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class ShelterAnimal extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'shelter_id',
        'pet_id',
        'status',
    ];
}
