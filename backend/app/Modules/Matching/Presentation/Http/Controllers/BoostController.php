<?php

declare(strict_types=1);

namespace App\Modules\Matching\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Matching\Application\Commands\BoostPet\BoostPetCommand;
use App\Modules\Matching\Application\Commands\BoostPet\BoostPetHandler;
use App\Modules\Matching\Domain\Exceptions\BoostQuotaExceededException;
use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Presentation\Http\Resources\PetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BoostController
{
    public function store(string $petId, Request $request, BoostPetHandler $handler): JsonResponse
    {
        try {
            $pet = $handler->handle(new BoostPetCommand($petId, $this->authenticatedUserId($request)));
        } catch (PetNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (BoostQuotaExceededException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => 'quota_exceeded'], 402);
        }

        return response()->json(['data' => new PetResource($pet)]);
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
