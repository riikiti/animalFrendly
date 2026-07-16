<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Repositories;

use App\Modules\Profile\Domain\Entities\Pet;
use App\Shared\Domain\ValueObjects\Id;

interface PetRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Pet $pet): void;

    public function findById(Id $id): ?Pet;

    /**
     * @return list<Pet>
     */
    public function findByOwner(Id $ownerId): array;

    /**
     * Количество анкет владельца — используется для лимита бесплатного тарифа
     * (см. CreatePetHandler), дешевле, чем count(findByOwner()).
     */
    public function countByOwner(Id $ownerId): int;

    /**
     * Активные анкеты не указанного владельца и не из списка исключений — используется
     * лентой подбора модуля Matching (см. docs/plan/07-flow-matching-shelter.md).
     *
     * @param  list<Id>  $excludeIds
     * @return list<Pet>
     */
    public function findActiveExcluding(Id $excludeOwnerId, array $excludeIds, int $limit): array;

    /**
     * Курсорная постраничная выборка всех питомцев (включая неактивных — переиндексация
     * должна и удалять из поискового индекса тех, кто больше не подходит), упорядоченная по
     * id (ULID монотонно возрастает) — используется полным реиндексом Search.
     *
     * @return list<Pet>
     */
    public function findAllForReindex(?string $afterId, int $limit): array;
}
