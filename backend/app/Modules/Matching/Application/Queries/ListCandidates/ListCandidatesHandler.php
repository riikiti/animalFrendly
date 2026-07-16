<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListCandidates;

use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Matching\Domain\Repositories\SwipeRepositoryInterface;
use App\Modules\Matching\Domain\Support\CandidateScorer;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListCandidatesHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly SwipeRepositoryInterface $swipes,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return list<Pet>
     */
    public function handle(ListCandidatesQuery $query): array
    {
        $swiperPetId = Id::fromString($query->swiperPetId);
        $actingUserId = Id::fromString($query->actingUserId);

        $swiperPet = $this->pets->findById($swiperPetId);

        if ($swiperPet === null) {
            throw PetNotFoundException::forId($query->swiperPetId);
        }

        if (! $swiperPet->ownerId()->equals($actingUserId)) {
            throw PetNotOwnedByActorException::create();
        }

        $excludeIds = [...$this->swipes->swipedTargetIds($swiperPetId), $swiperPetId];

        // Пул кандидатов для скоринга шире, чем финальная выдача — фильтр по виду уже
        // применён в SQL (findActiveExcluding), взвешенный скор по породе/интересам/
        // возрасту/расстоянию досчитывается здесь, в PHP.
        $poolSize = min(max($query->limit * 4, 60), 150);
        $candidates = $this->pets->findActiveExcluding($actingUserId, $excludeIds, $swiperPet->speciesId(), $poolSize);

        $swiperOwner = $this->users->findById($actingUserId);
        $swiperTags = array_map(static fn ($tag) => $tag->value, $swiperPet->socialTags());

        $scored = array_map(
            function (Pet $candidate) use ($swiperPet, $swiperTags, $swiperOwner): array {
                $candidateOwner = $this->users->findById($candidate->ownerId());

                $score = CandidateScorer::score(
                    swiperBreedId: $swiperPet->breedId(),
                    swiperTags: $swiperTags,
                    swiperBirthdate: $swiperPet->birthdate(),
                    swiperLat: $swiperOwner?->latitude(),
                    swiperLng: $swiperOwner?->longitude(),
                    candidateBreedId: $candidate->breedId(),
                    candidateTags: array_map(static fn ($tag) => $tag->value, $candidate->socialTags()),
                    candidateBirthdate: $candidate->birthdate(),
                    candidateLat: $candidateOwner?->latitude(),
                    candidateLng: $candidateOwner?->longitude(),
                );

                return ['pet' => $candidate, 'score' => $score];
            },
            $candidates,
        );

        // usort стабилен в PHP 8+ — при равном скоре сохраняется порядок, в котором
        // кандидаты пришли из SQL (буст → created_at DESC), отдельный тайбрейк не нужен.
        usort(
            $scored,
            static fn (array $a, array $b): int => ($b['pet']->isBoosted() <=> $a['pet']->isBoosted())
                ?: ($b['score'] <=> $a['score']),
        );

        return array_slice(array_map(static fn (array $entry) => $entry['pet'], $scored), 0, $query->limit);
    }
}
