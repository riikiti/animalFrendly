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
     * Количество анкет владельца с purpose social/breeding — то есть "анкет для мэтчинга",
     * используется для лимита бесплатного тарифа (см. CreatePetHandler). Не учитывает
     * for_sale/shelter — те создаются тем же CreatePetHandler из Marketplace/Shelter модулей
     * (см. CreateListingHandler/PublishShelterAnimalHandler) и лимиту не подчиняются: продавец
     * или приют должны иметь возможность завести сколько угодно объявлений/подопечных.
     */
    public function countByOwnerForMatching(Id $ownerId): int;

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
