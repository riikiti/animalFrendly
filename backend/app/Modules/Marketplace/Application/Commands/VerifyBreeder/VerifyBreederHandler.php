<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\VerifyBreeder;

use App\Modules\Marketplace\Domain\Entities\Breeder;
use App\Modules\Marketplace\Domain\Exceptions\BreederNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\BreederRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Право на верификацию (модератор/админ) проверяется на границе (Presentation) через
 * Gate::authorize('verify-breeders') — см. docs/rules/01-backend.md. Здесь — только
 * доменный переход статуса.
 */
final class VerifyBreederHandler
{
    public function __construct(private readonly BreederRepositoryInterface $breeders) {}

    public function handle(VerifyBreederCommand $command): Breeder
    {
        $breeder = $this->breeders->findById(Id::fromString($command->breederId));

        if ($breeder === null) {
            throw BreederNotFoundException::forId($command->breederId);
        }

        $moderatorId = Id::fromString($command->moderatorUserId);

        if ($command->approve) {
            $breeder->verify($moderatorId);
        } else {
            $breeder->reject($moderatorId);
        }

        $this->breeders->save($breeder);

        return $breeder;
    }
}
