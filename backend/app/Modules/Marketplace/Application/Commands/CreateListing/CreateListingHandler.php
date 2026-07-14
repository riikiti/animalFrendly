<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\CreateListing;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetCommand;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetHandler;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Как и PublishShelterAnimalHandler, создание листинга — это (1) анкета питомца в Profile
 * с purpose=for_sale и (2) сам листинг. Переиспользуем CreatePetHandler, не дублируем его
 * правила — см. docs/plan/03-architecture.md.
 */
final class CreateListingHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
        private readonly CreatePetHandler $createPet,
    ) {}

    public function handle(CreateListingCommand $command): CreateListingResult
    {
        $pet = $this->createPet->handle(new CreatePetCommand(
            ownerId: $command->sellerId,
            speciesId: $command->speciesId,
            breedId: $command->breedId,
            name: $command->name,
            sex: $command->sex,
            birthdate: $command->birthdate,
            purpose: 'for_sale',
            description: $command->description,
            isVaccinated: $command->isVaccinated,
        ));

        $listing = Listing::create(
            $this->listings->nextIdentity(),
            Id::fromString($command->sellerId),
            $pet->id(),
            Money::fromMinorUnits($command->priceAmount, $command->currency),
        );

        $this->listings->save($listing);

        return new CreateListingResult($listing, $pet);
    }
}
