<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shelter\Domain\Entities\ShelterAnimal as DomainShelterAnimal;
use App\Modules\Shelter\Domain\Enums\ShelterAnimalStatus;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Models\ShelterAnimal as EloquentShelterAnimal;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentShelterAnimalRepository implements ShelterAnimalRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainShelterAnimal $shelterAnimal): void
    {
        EloquentShelterAnimal::query()->updateOrCreate(
            ['id' => $shelterAnimal->id()->toString()],
            [
                'shelter_id' => $shelterAnimal->shelterId()->toString(),
                'pet_id' => $shelterAnimal->petId()->toString(),
                'status' => $shelterAnimal->status()->value,
            ],
        );
    }

    public function findById(Id $id): ?DomainShelterAnimal
    {
        $model = EloquentShelterAnimal::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findAvailable(int $limit = 20): array
    {
        return array_values(
            EloquentShelterAnimal::query()
                ->where('status', 'available')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findByShelter(Id $shelterId): array
    {
        return array_values(
            EloquentShelterAnimal::query()
                ->where('shelter_id', $shelterId->toString())
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentShelterAnimal $model): DomainShelterAnimal
    {
        return DomainShelterAnimal::reconstitute(
            id: Id::fromString($model->id),
            shelterId: Id::fromString($model->shelter_id),
            petId: Id::fromString($model->pet_id),
            status: ShelterAnimalStatus::from($model->status),
        );
    }
}
