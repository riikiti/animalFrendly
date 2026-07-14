<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\Catalog\SpeciesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Species extends Model
{
    /** @use HasFactory<SpeciesFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'species';

    protected $fillable = [
        'slug',
        'name_ru',
        'icon_media_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<Breed, $this>
     */
    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    protected static function newFactory(): SpeciesFactory
    {
        return SpeciesFactory::new();
    }
}
