<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Queries\ListPetPhotos;

use App\Modules\Profile\Domain\Entities\PetPhoto;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListPetPhotosHandler
{
    public function __construct(private readonly PetPhotoRepositoryInterface $photos) {}

    /**
     * @return list<PetPhoto>
     */
    public function handle(ListPetPhotosQuery $query): array
    {
        return $this->photos->findByPetId(Id::fromString($query->petId));
    }
}
