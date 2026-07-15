<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Repositories;

use App\Modules\Profile\Domain\Entities\PetPhoto;
use App\Shared\Domain\ValueObjects\Id;

interface PetPhotoRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(PetPhoto $photo): void;

    public function findById(Id $id): ?PetPhoto;

    /**
     * @return list<PetPhoto>
     */
    public function findByPetId(Id $petId): array;

    public function countForPet(Id $petId): int;

    /**
     * Снимает флаг "обложка" со всех фото питомца, кроме $exceptPhotoId (если передан).
     */
    public function clearPrimaryForPet(Id $petId, ?Id $exceptPhotoId = null): void;

    public function delete(Id $id): void;
}
