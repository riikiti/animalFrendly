<?php

declare(strict_types=1);

namespace App\Modules\Matching\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Matching\Application\Commands\RecordSwipe\RecordSwipeCommand;
use App\Modules\Matching\Application\Commands\RecordSwipe\RecordSwipeHandler;
use App\Modules\Matching\Application\Queries\ListCandidates\ListCandidatesHandler;
use App\Modules\Matching\Application\Queries\ListCandidates\ListCandidatesQuery;
use App\Modules\Matching\Application\Queries\ListMatches\ListMatchesHandler;
use App\Modules\Matching\Application\Queries\ListMatches\ListMatchesQuery;
use App\Modules\Matching\Application\Queries\ListPendingLikes\ListPendingLikesHandler;
use App\Modules\Matching\Application\Queries\ListPendingLikes\ListPendingLikesQuery;
use App\Modules\Matching\Domain\Exceptions\CannotSwipeOwnPetException;
use App\Modules\Matching\Domain\Exceptions\PetAlreadySwipedException;
use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Matching\Domain\Exceptions\SwipeQuotaExceededException;
use App\Modules\Matching\Presentation\Http\Requests\StoreSwipeRequest;
use App\Modules\Matching\Presentation\Http\Resources\MatchResource;
use App\Modules\Profile\Presentation\Http\Resources\PetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SwipeController
{
    public function candidates(string $petId, Request $request, ListCandidatesHandler $handler): JsonResponse
    {
        try {
            $candidates = $handler->handle(new ListCandidatesQuery(
                actingUserId: $this->authenticatedUserId($request),
                swiperPetId: $petId,
            ));
        } catch (PetNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => PetResource::collection($candidates)]);
    }

    public function store(string $petId, StoreSwipeRequest $request, RecordSwipeHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new RecordSwipeCommand(
                actingUserId: $this->authenticatedUserId($request),
                swiperPetId: $petId,
                targetPetId: $request->string('target_pet_id')->toString(),
                action: $request->string('action')->toString(),
            ));
        } catch (PetNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (CannotSwipeOwnPetException|PetAlreadySwipedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (SwipeQuotaExceededException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => 'quota_exceeded'], 402);
        }

        return response()->json([
            'is_match' => $result->isMatch(),
            'match' => $result->match !== null ? new MatchResource($result->match) : null,
        ], 201);
    }

    public function matches(string $petId, Request $request, ListMatchesHandler $handler): JsonResponse
    {
        try {
            $matches = $handler->handle(new ListMatchesQuery(
                actingUserId: $this->authenticatedUserId($request),
                petId: $petId,
            ));
        } catch (PetNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => MatchResource::collection($matches)]);
    }

    public function pendingLikes(string $petId, Request $request, ListPendingLikesHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new ListPendingLikesQuery(
                actingUserId: $this->authenticatedUserId($request),
                petId: $petId,
            ));
        } catch (PetNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => [
            'received' => PetResource::collection($result->received),
            'sent' => PetResource::collection($result->sent),
        ]]);
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
