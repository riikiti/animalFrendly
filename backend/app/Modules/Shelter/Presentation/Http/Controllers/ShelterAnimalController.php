<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Shelter\Application\Commands\PublishShelterAnimal\PublishShelterAnimalCommand;
use App\Modules\Shelter\Application\Commands\PublishShelterAnimal\PublishShelterAnimalHandler;
use App\Modules\Shelter\Application\Queries\ListAvailableShelterAnimals\ListAvailableShelterAnimalsHandler;
use App\Modules\Shelter\Application\Queries\ListMyShelterAnimals\ListMyShelterAnimalsHandler;
use App\Modules\Shelter\Application\Queries\ListMyShelterAnimals\ListMyShelterAnimalsQuery;
use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotVerifiedException;
use App\Modules\Shelter\Presentation\Http\Requests\StoreShelterAnimalRequest;
use App\Modules\Shelter\Presentation\Http\Resources\ShelterAnimalResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShelterAnimalController
{
    public function index(ListAvailableShelterAnimalsHandler $handler, PetRepositoryInterface $pets): JsonResponse
    {
        $data = array_map(
            static fn (ShelterAnimal $animal) => new ShelterAnimalResource([
                'animal' => $animal,
                'pet' => $pets->findById($animal->petId()),
            ]),
            $handler->handle(),
        );

        return response()->json(['data' => $data]);
    }

    public function store(
        string $shelterId,
        StoreShelterAnimalRequest $request,
        PublishShelterAnimalHandler $handler,
    ): JsonResponse {
        try {
            $shelterAnimal = $handler->handle(new PublishShelterAnimalCommand(
                shelterId: $shelterId,
                actingUserId: $this->authenticatedUserId($request),
                speciesId: $request->integer('species_id'),
                breedId: $request->integer('breed_id') ?: null,
                name: $request->string('name')->toString(),
                sex: $request->string('sex')->toString(),
                birthdate: $request->string('birthdate')->toString() ?: null,
                description: $request->string('description')->toString() ?: null,
                isVaccinated: $request->boolean('is_vaccinated'),
            ));
        } catch (ShelterNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotShelterOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ShelterNotVerifiedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => new ShelterAnimalResource(['animal' => $shelterAnimal, 'pet' => null]),
        ], 201);
    }

    public function mine(
        string $shelterId,
        Request $request,
        ListMyShelterAnimalsHandler $handler,
        PetRepositoryInterface $pets,
    ): JsonResponse {
        try {
            $animals = $handler->handle(new ListMyShelterAnimalsQuery(
                shelterId: $shelterId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (ShelterNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotShelterOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $data = array_map(
            static fn (ShelterAnimal $animal) => new ShelterAnimalResource([
                'animal' => $animal,
                'pet' => $pets->findById($animal->petId()),
            ]),
            $animals,
        );

        return response()->json(['data' => $data]);
    }

    private function authenticatedUserId(Request $request): string
    {
        $user = $request->user();

        if (! $user instanceof IdentityUser) {
            abort(401);
        }

        return $user->id;
    }
}
