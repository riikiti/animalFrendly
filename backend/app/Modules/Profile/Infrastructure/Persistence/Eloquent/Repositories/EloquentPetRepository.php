<?php

declare(strict_types=1);

namespace App\Modules\Profile\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Profile\Domain\Entities\Pet as DomainPet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Enums\PetStatus;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\Pet as EloquentPet;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentPetRepository implements PetRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainPet $pet): void
    {
        EloquentPet::query()->updateOrCreate(
            ['id' => $pet->id()->toString()],
            [
                'owner_id' => $pet->ownerId()->toString(),
                'species_id' => $pet->speciesId(),
                'breed_id' => $pet->breedId(),
                'name' => $pet->name(),
                'sex' => $pet->sex()->value,
                'birthdate' => $pet->birthdate()?->format('Y-m-d'),
                'purpose' => $pet->purpose()->value,
                'description' => $pet->description(),
                'is_vaccinated' => $pet->isVaccinated(),
                'status' => $pet->status()->value,
                'boosted_until' => $pet->boostedUntil(),
                'photo_url' => $pet->photoUrl(),
            ],
        );
    }

    public function findById(Id $id): ?DomainPet
    {
        $model = EloquentPet::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOwner(Id $ownerId): array
    {
        return array_values(
            EloquentPet::query()
                ->where('owner_id', $ownerId->toString())
                ->orderBy('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findActiveExcluding(Id $excludeOwnerId, array $excludeIds, int $limit): array
    {
        $excludeIdStrings = array_map(static fn (Id $id): string => $id->toString(), $excludeIds);

        return array_values(
            EloquentPet::query()
                ->where('status', 'active')
                ->where('owner_id', '!=', $excludeOwnerId->toString())
                ->when($excludeIdStrings !== [], fn ($query) => $query->whereNotIn('id', $excludeIdStrings))
                ->orderByRaw('CASE WHEN boosted_until IS NOT NULL AND boosted_until > ? THEN 0 ELSE 1 END', [now()])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findAllForReindex(?string $afterId, int $limit): array
    {
        return array_values(
            EloquentPet::query()
                ->when($afterId !== null, fn ($query) => $query->where('id', '>', $afterId))
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentPet $model): DomainPet
    {
        return DomainPet::reconstitute(
            id: Id::fromString($model->id),
            ownerId: Id::fromString($model->owner_id),
            speciesId: $model->species_id,
            breedId: $model->breed_id,
            name: $model->name,
            sex: PetSex::from($model->sex),
            birthdate: $model->birthdate?->toDateTimeImmutable(),
            purpose: PetPurpose::from($model->purpose),
            description: $model->description,
            isVaccinated: $model->is_vaccinated,
            status: PetStatus::from($model->status),
            boostedUntil: $model->boosted_until?->toDateTimeImmutable(),
            photoUrl: $model->photo_url,
        );
    }
}
