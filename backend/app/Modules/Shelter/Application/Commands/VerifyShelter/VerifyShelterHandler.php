<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\VerifyShelter;

use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Право на верификацию (модератор/админ) проверяется на границе (Presentation) —
 * см. docs/rules/01-backend.md. Здесь — только доменный переход статуса.
 */
final class VerifyShelterHandler
{
    public function __construct(private readonly ShelterRepositoryInterface $shelters) {}

    public function handle(VerifyShelterCommand $command): Shelter
    {
        $shelter = $this->shelters->findById(Id::fromString($command->shelterId));

        if ($shelter === null) {
            throw ShelterNotFoundException::forId($command->shelterId);
        }

        $moderatorId = Id::fromString($command->moderatorUserId);

        if ($command->approve) {
            $shelter->verify($moderatorId);
        } else {
            $shelter->reject($moderatorId);
        }

        $this->shelters->save($shelter);

        return $shelter;
    }
}
