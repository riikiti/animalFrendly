<?php

declare(strict_types=1);

namespace App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\Profile\PetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Pet extends Model
{
    /** @use HasFactory<PetFactory> */
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'owner_id',
        'species_id',
        'breed_id',
        'parent_pet_id',
        'name',
        'sex',
        'birthdate',
        'purpose',
        'description',
        'health_notes',
        'is_vaccinated',
        'status',
        'boosted_until',
        'photo_url',
        'social_tags',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'is_vaccinated' => 'boolean',
        'boosted_until' => 'datetime',
        'social_tags' => 'array',
    ];

    protected static function newFactory(): PetFactory
    {
        return PetFactory::new();
    }
}
