<?php

declare(strict_types=1);

namespace App\Modules\Profile\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Media\Domain\Exceptions\InvalidMediaUploadException;
use App\Modules\Profile\Application\Commands\AddPetPhoto\AddPetPhotoCommand;
use App\Modules\Profile\Application\Commands\AddPetPhoto\AddPetPhotoHandler;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetCommand;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetHandler;
use App\Modules\Profile\Application\Commands\RemovePetPhoto\RemovePetPhotoCommand;
use App\Modules\Profile\Application\Commands\RemovePetPhoto\RemovePetPhotoHandler;
use App\Modules\Profile\Application\Commands\SetPetPhotoCover\SetPetPhotoCoverCommand;
use App\Modules\Profile\Application\Commands\SetPetPhotoCover\SetPetPhotoCoverHandler;
use App\Modules\Profile\Application\Queries\ListMyPets\ListMyPetsHandler;
use App\Modules\Profile\Application\Queries\ListPetPhotos\ListPetPhotosHandler;
use App\Modules\Profile\Application\Queries\ListPetPhotos\ListPetPhotosQuery;
use App\Modules\Profile\Domain\Exceptions\BreedDoesNotBelongToSpeciesException;
use App\Modules\Profile\Domain\Exceptions\PetLimitExceededException;
use App\Modules\Profile\Domain\Exceptions\PetNotFoundException;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Exceptions\PetPhotoNotFoundException;
use App\Modules\Profile\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Profile\Domain\Exceptions\TooManyPetPhotosException;
use App\Modules\Profile\Presentation\Http\Requests\AddPetPhotoRequest;
use App\Modules\Profile\Presentation\Http\Requests\StorePetRequest;
use App\Modules\Profile\Presentation\Http\Resources\PetPhotoResource;
use App\Modules\Profile\Presentation\Http\Resources\PetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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
                socialTags: $request->input('social_tags', []),
            ));
        } catch (SpeciesNotFoundException|BreedDoesNotBelongToSpeciesException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PetLimitExceededException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => 'pet_limit_exceeded'], 402);
        }

        return response()->json(['data' => new PetResource($pet)], 201);
    }

    public function listPhotos(string $petId, ListPetPhotosHandler $handler): JsonResponse
    {
        return response()->json([
            'data' => PetPhotoResource::collection($handler->handle(new ListPetPhotosQuery($petId))),
        ]);
    }

    public function addPhoto(string $petId, AddPetPhotoRequest $request, AddPetPhotoHandler $handler): JsonResponse
    {
        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            return response()->json(['message' => 'Файл не передан.'], 422);
        }

        try {
            $petPhoto = $handler->handle(new AddPetPhotoCommand(
                petId: $petId,
                actingUserId: $this->authenticatedUserId($request),
                photo: $photo,
            ));
        } catch (PetNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (TooManyPetPhotosException|InvalidMediaUploadException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new PetPhotoResource($petPhoto)], 201);
    }

    public function removePhoto(string $petId, string $photoId, Request $request, RemovePetPhotoHandler $handler): JsonResponse
    {
        try {
            $handler->handle(new RemovePetPhotoCommand(
                petId: $petId,
                photoId: $photoId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (PetNotFoundException|PetPhotoNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => ['ok' => true]]);
    }

    public function setPhotoCover(string $petId, string $photoId, Request $request, SetPetPhotoCoverHandler $handler): JsonResponse
    {
        try {
            $petPhoto = $handler->handle(new SetPetPhotoCoverCommand(
                petId: $petId,
                photoId: $photoId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (PetNotFoundException|PetPhotoNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => new PetPhotoResource($petPhoto)]);
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
