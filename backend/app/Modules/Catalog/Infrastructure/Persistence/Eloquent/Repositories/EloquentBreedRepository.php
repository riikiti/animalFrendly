<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Catalog\Domain\Entities\Breed as DomainBreed;
use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Breed as EloquentBreed;

final class EloquentBreedRepository implements BreedRepositoryInterface
{
    public function activeBySpeciesId(int $speciesId): array
    {
        return array_values(
            EloquentBreed::query()
                ->where('species_id', $speciesId)
                ->where('is_active', true)
                ->orderBy('name_ru')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentBreed $model): DomainBreed
    {
        return new DomainBreed(
            id: $model->id,
            speciesId: $model->species_id,
            slug: $model->slug,
            nameRu: $model->name_ru,
            attributes: $model->attributes ?? [],
            isActive: $model->is_active,
        );
    }
}
