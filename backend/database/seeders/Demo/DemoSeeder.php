<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Breed;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserHandler;
use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarCommand;
use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarHandler;
use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileCommand;
use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileHandler;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Marketplace\Application\Commands\CreateListing\CreateListingCommand;
use App\Modules\Marketplace\Application\Commands\CreateListing\CreateListingHandler;
use App\Modules\Marketplace\Application\Commands\PublishListing\PublishListingCommand;
use App\Modules\Marketplace\Application\Commands\PublishListing\PublishListingHandler;
use App\Modules\Marketplace\Application\Commands\RegisterBreeder\RegisterBreederCommand;
use App\Modules\Marketplace\Application\Commands\RegisterBreeder\RegisterBreederHandler;
use App\Modules\Marketplace\Application\Commands\VerifyBreeder\VerifyBreederCommand;
use App\Modules\Marketplace\Application\Commands\VerifyBreeder\VerifyBreederHandler;
use App\Modules\Profile\Application\Commands\AddPetPhoto\AddPetPhotoCommand;
use App\Modules\Profile\Application\Commands\AddPetPhoto\AddPetPhotoHandler;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetCommand;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetHandler;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Shelter\Application\Commands\PublishShelterAnimal\PublishShelterAnimalCommand;
use App\Modules\Shelter\Application\Commands\PublishShelterAnimal\PublishShelterAnimalHandler;
use App\Modules\Shelter\Application\Commands\RegisterShelter\RegisterShelterCommand;
use App\Modules\Shelter\Application\Commands\RegisterShelter\RegisterShelterHandler;
use App\Modules\Shelter\Application\Commands\UpdateShelter\UpdateShelterCommand;
use App\Modules\Shelter\Application\Commands\UpdateShelter\UpdateShelterHandler;
use App\Modules\Shelter\Application\Commands\UpdateShelterPhoto\UpdateShelterPhotoCommand;
use App\Modules\Shelter\Application\Commands\UpdateShelterPhoto\UpdateShelterPhotoHandler;
use App\Modules\Shelter\Application\Commands\VerifyShelter\VerifyShelterCommand;
use App\Modules\Shelter\Application\Commands\VerifyShelter\VerifyShelterHandler;
use Database\Seeders\Catalog\CatalogSeeder;
use Database\Seeders\Support\DemoDescriptions;
use Database\Seeders\Support\RandomAnimalPhotoDownloader;
use Illuminate\Database\Seeder;

/**
 * Наполняет dev-БД реалистичными демо-данными для ручного браузинга приложения — не
 * вызывается автотестами (Pest не сеет его, см. tests/Pest.php) и намеренно не запускается
 * вне local-окружения (сетевые загрузки фото + произвольный объём данных не годятся ни для
 * CI, ни тем более для прод). Не идемпотентен по дизайну — рассчитан на один запуск на
 * свежую БД, повторный запуск определяется по зарезервированному номеру телефона модератора
 * и просто ничего не делает.
 */
final class DemoSeeder extends Seeder
{
    private const MARKER_PHONE = '+79990000000';

    private DemoDescriptions $descriptions;

    private RandomAnimalPhotoDownloader $photos;

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoSeeder пропущен — не local-окружение.');

            return;
        }

        if (User::query()->where('phone', self::MARKER_PHONE)->exists()) {
            $this->command?->warn('Демо-данные уже засеяны (найден маркерный пользователь) — пропускаю.');

            return;
        }

        $this->call(CatalogSeeder::class);

        $this->descriptions = new DemoDescriptions;
        $this->photos = new RandomAnimalPhotoDownloader;

        $this->command?->info('Создаю модератора и владельцев с анкетами…');
        $moderatorId = $this->createModerator();

        $ownerIds = [];
        foreach (range(1, 12) as $index) {
            $ownerIds[] = $this->createOwnerWithPet($index);
        }

        $this->command?->info('Регистрирую заводчиков и объявления…');
        $this->createBreederWithListings($ownerIds[0], $moderatorId);
        $this->createBreederWithListings($ownerIds[1], $moderatorId);
        $this->createListingsForOwner($ownerIds[2]);

        $this->command?->info('Создаю приюты с животными…');
        foreach (range(1, 3) as $index) {
            $this->createShelterWithAnimals($index, $moderatorId);
        }

        $this->command?->info('Демо-данные готовы.');
    }

    private function createModerator(): string
    {
        $handler = app(RegisterUserHandler::class);
        $user = $handler->handle(new RegisterUserCommand(
            phone: self::MARKER_PHONE,
            password: 'demo-password',
            accountType: 'moderator',
            personalDataConsentGiven: true,
        ));

        return $user->id()->toString();
    }

    private function createOwnerWithPet(int $index): string
    {
        $phone = sprintf('+7999%07d', $index);
        $name = $this->descriptions->ownerName();

        $userId = app(RegisterUserHandler::class)->handle(new RegisterUserCommand(
            phone: $phone,
            password: 'demo-password',
            accountType: 'owner',
            personalDataConsentGiven: true,
        ))->id()->toString();

        app(UpdateProfileHandler::class)->handle(new UpdateProfileCommand(
            userId: $userId,
            name: $name['first'].' '.$name['last'],
            address: $this->descriptions->address(),
        ));

        $avatar = $this->photos->humanFace();
        if ($avatar !== null) {
            app(UpdateAvatarHandler::class)->handle(new UpdateAvatarCommand($userId, $avatar));
        }

        $speciesSlug = fake()->randomElement(['dog', 'dog', 'cat', 'cat', 'bird']);
        $pet = $this->createPet($userId, $speciesSlug, 'social');
        $this->attachPetPhoto($pet, $speciesSlug);

        return $userId;
    }

    private function createBreederWithListings(string $ownerId, string $moderatorId): void
    {
        $breederId = app(RegisterBreederHandler::class)->handle(new RegisterBreederCommand($ownerId))->id()->toString();

        app(VerifyBreederHandler::class)->handle(new VerifyBreederCommand(
            breederId: $breederId,
            moderatorUserId: $moderatorId,
            approve: true,
        ));

        $this->createListingsForOwner($ownerId);
    }

    private function createListingsForOwner(string $ownerId): void
    {
        foreach (range(1, 2) as $_) {
            $speciesSlug = fake()->randomElement(['dog', 'cat']);
            [$speciesId, $breedId] = $this->pickSpeciesAndBreed($speciesSlug);

            $listing = app(CreateListingHandler::class)->handle(new CreateListingCommand(
                sellerId: $ownerId,
                speciesId: $speciesId,
                breedId: $breedId,
                name: $this->descriptions->petName($speciesSlug),
                sex: fake()->randomElement(['male', 'female']),
                birthdate: $this->randomBirthdate(),
                description: $this->descriptions->petDescription($speciesSlug),
                isVaccinated: fake()->boolean(70),
                priceAmount: fake()->numberBetween(5000, 60000) * 100,
            ));

            app(PublishListingHandler::class)->handle(new PublishListingCommand(
                listingId: $listing->listing->id()->toString(),
                actingUserId: $ownerId,
            ));

            $this->attachPetPhoto($listing->pet, $speciesSlug);
        }
    }

    private function createShelterWithAnimals(int $index, string $moderatorId): void
    {
        $phone = sprintf('+7999010%04d', $index);
        $ownerId = app(RegisterUserHandler::class)->handle(new RegisterUserCommand(
            phone: $phone,
            password: 'demo-password',
            accountType: 'owner',
            personalDataConsentGiven: true,
        ))->id()->toString();

        $name = $this->descriptions->ownerName();
        app(UpdateProfileHandler::class)->handle(new UpdateProfileCommand(
            userId: $ownerId,
            name: $name['first'].' '.$name['last'],
            address: $this->descriptions->address(),
        ));

        $shelter = app(RegisterShelterHandler::class)->handle(new RegisterShelterCommand(
            ownerUserId: $ownerId,
            legalName: $this->descriptions->shelterName(),
            inn: null,
            description: $this->descriptions->shelterDescription(),
        ));
        $shelterId = $shelter->id()->toString();

        app(VerifyShelterHandler::class)->handle(new VerifyShelterCommand(
            shelterId: $shelterId,
            moderatorUserId: $moderatorId,
            approve: true,
        ));

        app(UpdateShelterHandler::class)->handle(new UpdateShelterCommand(
            shelterId: $shelterId,
            actingUserId: $ownerId,
            phone: '+7'.fake()->numerify('9#########'),
            email: fake()->unique()->safeEmail(),
            address: $this->descriptions->address(),
        ));

        $photo = $this->photos->forSpecies('dog');
        if ($photo !== null) {
            app(UpdateShelterPhotoHandler::class)->handle(new UpdateShelterPhotoCommand($shelterId, $ownerId, $photo));
        }

        foreach (range(1, fake()->numberBetween(2, 3)) as $_) {
            $speciesSlug = fake()->randomElement(['dog', 'cat']);
            [$speciesId, $breedId] = $this->pickSpeciesAndBreed($speciesSlug);

            $animal = app(PublishShelterAnimalHandler::class)->handle(new PublishShelterAnimalCommand(
                shelterId: $shelterId,
                actingUserId: $ownerId,
                speciesId: $speciesId,
                breedId: $breedId,
                name: $this->descriptions->petName($speciesSlug),
                sex: fake()->randomElement(['male', 'female']),
                birthdate: $this->randomBirthdate(),
                description: $this->descriptions->petDescription($speciesSlug),
                isVaccinated: true,
            ));

            $photo = $this->photos->forSpecies($speciesSlug);
            if ($photo !== null) {
                app(AddPetPhotoHandler::class)->handle(new AddPetPhotoCommand(
                    petId: $animal->petId()->toString(),
                    actingUserId: $ownerId,
                    photo: $photo,
                ));
            }
        }
    }

    private function createPet(string $ownerId, string $speciesSlug, string $purpose): Pet
    {
        [$speciesId, $breedId] = $this->pickSpeciesAndBreed($speciesSlug);

        return app(CreatePetHandler::class)->handle(new CreatePetCommand(
            ownerId: $ownerId,
            speciesId: $speciesId,
            breedId: $breedId,
            name: $this->descriptions->petName($speciesSlug),
            sex: fake()->randomElement(['male', 'female']),
            birthdate: $this->randomBirthdate(),
            purpose: $purpose,
            description: $this->descriptions->petDescription($speciesSlug),
            isVaccinated: fake()->boolean(70),
        ));
    }

    private function attachPetPhoto(Pet $pet, string $speciesSlug): void
    {
        $photo = $this->photos->forSpecies($speciesSlug);

        if ($photo === null) {
            return;
        }

        app(AddPetPhotoHandler::class)->handle(new AddPetPhotoCommand(
            petId: $pet->id()->toString(),
            actingUserId: $pet->ownerId()->toString(),
            photo: $photo,
        ));
    }

    /**
     * @return array{0: int, 1: ?int}
     */
    private function pickSpeciesAndBreed(string $speciesSlug): array
    {
        $species = Species::query()->where('slug', $speciesSlug)->firstOrFail();
        $breed = Breed::query()->where('species_id', $species->id)->inRandomOrder()->first();

        return [$species->id, $breed?->id];
    }

    private function randomBirthdate(): string
    {
        return now()->subMonths(random_int(3, 96))->format('Y-m-d');
    }
}
