<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\RegisterBreeder;

use App\Modules\Marketplace\Domain\Entities\Breeder;
use App\Modules\Marketplace\Domain\Repositories\BreederRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class RegisterBreederHandler
{
    public function __construct(private readonly BreederRepositoryInterface $breeders) {}

    public function handle(RegisterBreederCommand $command): Breeder
    {
        $breeder = Breeder::register(
            id: $this->breeders->nextIdentity(),
            ownerUserId: Id::fromString($command->ownerUserId),
        );

        $this->breeders->save($breeder);

        return $breeder;
    }
}
