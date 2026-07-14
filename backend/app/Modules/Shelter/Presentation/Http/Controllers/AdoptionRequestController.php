<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Shelter\Application\Commands\CancelAdoptionRequest\CancelAdoptionRequestCommand;
use App\Modules\Shelter\Application\Commands\CancelAdoptionRequest\CancelAdoptionRequestHandler;
use App\Modules\Shelter\Application\Commands\DecideAdoptionRequest\DecideAdoptionRequestCommand;
use App\Modules\Shelter\Application\Commands\DecideAdoptionRequest\DecideAdoptionRequestHandler;
use App\Modules\Shelter\Application\Commands\SubmitAdoptionRequest\SubmitAdoptionRequestCommand;
use App\Modules\Shelter\Application\Commands\SubmitAdoptionRequest\SubmitAdoptionRequestHandler;
use App\Modules\Shelter\Application\Queries\ListMyAdoptionRequests\ListMyAdoptionRequestsHandler;
use App\Modules\Shelter\Application\Queries\ListPendingRequestsForShelter\ListPendingRequestsForShelterHandler;
use App\Modules\Shelter\Application\Queries\ListPendingRequestsForShelter\ListPendingRequestsForShelterQuery;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestAlreadyDecidedException;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestNotFoundException;
use App\Modules\Shelter\Domain\Exceptions\NotAdoptionRequesterException;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterAnimalNotAvailableException;
use App\Modules\Shelter\Domain\Exceptions\ShelterAnimalNotFoundException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Presentation\Http\Requests\DecideAdoptionRequestRequest;
use App\Modules\Shelter\Presentation\Http\Requests\StoreAdoptionRequestRequest;
use App\Modules\Shelter\Presentation\Http\Resources\AdoptionRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdoptionRequestController
{
    public function store(
        string $shelterAnimalId,
        StoreAdoptionRequestRequest $request,
        SubmitAdoptionRequestHandler $handler,
    ): JsonResponse {
        try {
            $adoptionRequest = $handler->handle(new SubmitAdoptionRequestCommand(
                shelterAnimalId: $shelterAnimalId,
                requesterUserId: $this->authenticatedUserId($request),
                message: $request->string('message')->toString() ?: null,
            ));
        } catch (ShelterAnimalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ShelterAnimalNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new AdoptionRequestResource($adoptionRequest)], 201);
    }

    public function myRequests(Request $request, ListMyAdoptionRequestsHandler $handler): JsonResponse
    {
        $requests = $handler->handle($this->authenticatedUserId($request));

        return response()->json(['data' => AdoptionRequestResource::collection($requests)]);
    }

    public function pendingForShelter(
        string $shelterId,
        Request $request,
        ListPendingRequestsForShelterHandler $handler,
    ): JsonResponse {
        try {
            $requests = $handler->handle(new ListPendingRequestsForShelterQuery(
                shelterId: $shelterId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (ShelterNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotShelterOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => AdoptionRequestResource::collection($requests)]);
    }

    public function decide(
        string $adoptionRequestId,
        DecideAdoptionRequestRequest $request,
        DecideAdoptionRequestHandler $handler,
    ): JsonResponse {
        try {
            $adoptionRequest = $handler->handle(new DecideAdoptionRequestCommand(
                adoptionRequestId: $adoptionRequestId,
                actingUserId: $this->authenticatedUserId($request),
                approve: $request->boolean('approve'),
            ));
        } catch (AdoptionRequestNotFoundException|ShelterAnimalNotFoundException|ShelterNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotShelterOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (AdoptionRequestAlreadyDecidedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new AdoptionRequestResource($adoptionRequest)]);
    }

    public function cancel(
        string $adoptionRequestId,
        Request $request,
        CancelAdoptionRequestHandler $handler,
    ): JsonResponse {
        try {
            $adoptionRequest = $handler->handle(new CancelAdoptionRequestCommand(
                adoptionRequestId: $adoptionRequestId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (AdoptionRequestNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotAdoptionRequesterException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (AdoptionRequestAlreadyDecidedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new AdoptionRequestResource($adoptionRequest)]);
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
