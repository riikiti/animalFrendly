<?php

declare(strict_types=1);

namespace App\Modules\Profile\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Profile\Domain\Entities\PetPhoto as DomainPetPhoto;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\PetPhoto as EloquentPetPhoto;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentPetPhotoRepository implements PetPhotoRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainPetPhoto $photo): void
    {
        EloquentPetPhoto::query()->updateOrCreate(
            ['id' => $photo->id()->toString()],
            [
                'pet_id' => $photo->petId()->toString(),
                'media_id' => $photo->mediaId()->toString(),
                'url' => $photo->url(),
                'is_primary' => $photo->isPrimary(),
                'created_at' => $photo->createdAt(),
            ],
        );
    }

    public function findById(Id $id): ?DomainPetPhoto
    {
        $model = EloquentPetPhoto::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByPetId(Id $petId): array
    {
        return array_values(
            EloquentPetPhoto::query()
                ->where('pet_id', $petId->toString())
                // ULID монотонен по времени создания — тот же приём, что
                // EloquentNotificationRepository::findByUser.
                ->orderBy('id')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function countForPet(Id $petId): int
    {
        return EloquentPetPhoto::query()->where('pet_id', $petId->toString())->count();
    }

    public function clearPrimaryForPet(Id $petId, ?Id $exceptPhotoId = null): void
    {
        $query = EloquentPetPhoto::query()->where('pet_id', $petId->toString());

        if ($exceptPhotoId !== null) {
            $query->where('id', '!=', $exceptPhotoId->toString());
        }

        $query->update(['is_primary' => false]);
    }

    public function delete(Id $id): void
    {
        EloquentPetPhoto::query()->where('id', $id->toString())->delete();
    }

    private function toDomain(EloquentPetPhoto $model): DomainPetPhoto
    {
        return DomainPetPhoto::reconstitute(
            id: Id::fromString($model->id),
            petId: Id::fromString($model->pet_id),
            mediaId: Id::fromString($model->media_id),
            url: $model->url,
            isPrimary: $model->is_primary,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
