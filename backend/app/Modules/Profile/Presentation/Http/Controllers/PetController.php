<?php

declare(strict_types=1);

namespace App\Modules\Profile\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetCommand;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetHandler;
use App\Modules\Profile\Application\Queries\ListMyPets\ListMyPetsHandler;
use App\Modules\Profile\Domain\Exceptions\BreedDoesNotBelongToSpeciesException;
use App\Modules\Profile\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Profile\Presentation\Http\Requests\StorePetRequest;
use App\Modules\Profile\Presentation\Http\Resources\PetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PetController
{
    public function index(Request $request, ListMyPetsHandler $handler): JsonResponse
    {
        $ownerId = $this->authenticatedUserId($request);

        return response()->json([
            'data' => PetResource::collection($handler->handle($ownerId)),
        ]);
    }

    public function store(StorePetRequest $request, CreatePetHandler $handler): JsonResponse
    {
        $ownerId = $this->authenticatedUserId($request);

        try {
            $pet = $handler->handle(new CreatePetCommand(
                ownerId: $ownerId,
                speciesId: $request->integer('species_id'),
                breedId: $request->integer('breed_id') ?: null,
                name: $request->string('name')->toString(),
                sex: $request->string('sex')->toString(),
                birthdate: $request->string('birthdate')->toString() ?: null,
                purpose: $request->string('purpose')->toString(),
                description: $request->string('description')->toString() ?: null,
                isVaccinated: $request->boolean('is_vaccinated'),
            ));
        } catch (SpeciesNotFoundException|BreedDoesNotBelongToSpeciesException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new PetResource($pet)], 201);
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
