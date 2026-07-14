<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Catalog\Domain\Entities\Species as DomainSpecies;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species as EloquentSpecies;

final class EloquentSpeciesRepository implements SpeciesRepositoryInterface
{
    public function allActive(): array
    {
        return array_values(
            EloquentSpecies::query()
                ->where('is_active', true)
                ->orderBy('name_ru')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findBySlug(string $slug): ?DomainSpecies
    {
        $model = EloquentSpecies::query()->where('slug', $slug)->where('is_active', true)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(int $id): ?DomainSpecies
    {
        $model = EloquentSpecies::query()->where('is_active', true)->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentSpecies $model): DomainSpecies
    {
        return new DomainSpecies(
            id: $model->id,
            slug: $model->slug,
            nameRu: $model->name_ru,
            isActive: $model->is_active,
        );
    }
}
