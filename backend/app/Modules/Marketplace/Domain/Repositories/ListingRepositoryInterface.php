<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Repositories;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Shared\Domain\ValueObjects\Id;

interface ListingRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Listing $listing): void;

    public function findById(Id $id): ?Listing;

    /**
     * @return list<Listing>
     */
    public function findPublished(): array;

    /**
     * @return list<Listing>
     */
    public function findBySeller(Id $sellerId): array;

    /**
     * Курсорная постраничная выборка всех листингов (любого статуса — переиндексация должна и
     * удалять из индекса те, что больше не published), упорядоченная по id — используется
     * полным реиндексом Search.
     *
     * @return list<Listing>
     */
    public function findAllForReindex(?string $afterId, int $limit): array;
}
