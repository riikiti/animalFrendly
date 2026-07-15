<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Indexing;

use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use App\Shared\Domain\ValueObjects\Id;

final class IndexPetService
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly BuildPetDocument $builder,
        private readonly PetSearchIndexInterface $index,
    ) {}

    public function index(Id $petId): void
    {
        $pet = $this->pets->findById($petId);

        if ($pet === null) {
            $this->index->deleteDocument($petId->toString());

            return;
        }

        $document = $this->builder->build($pet);

        if ($document === null) {
            $this->index->deleteDocument($petId->toString());

            return;
        }

        $this->index->putDocument($document);
    }
}
