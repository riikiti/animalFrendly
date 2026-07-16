<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Marketplace\Application\Commands\RegisterBreeder\RegisterBreederCommand;
use App\Modules\Marketplace\Application\Commands\RegisterBreeder\RegisterBreederHandler;
use App\Modules\Marketplace\Application\Commands\VerifyBreeder\VerifyBreederCommand;
use App\Modules\Marketplace\Application\Commands\VerifyBreeder\VerifyBreederHandler;
use App\Modules\Marketplace\Application\Queries\GetMyBreeder\GetMyBreederHandler;
use App\Modules\Marketplace\Application\Queries\ListPendingBreederVerifications\ListPendingBreederVerificationsHandler;
use App\Modules\Marketplace\Domain\Exceptions\BreederNotFoundException;
use App\Modules\Marketplace\Presentation\Http\Resources\BreederResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class BreederController
{
    public function store(Request $request, RegisterBreederHandler $handler): JsonResponse
    {
        $breeder = $handler->handle(new RegisterBreederCommand(
            ownerUserId: $this->authenticatedUserId($request),
        ));

        return response()->json(['data' => new BreederResource($breeder)], 201);
    }

    public function me(Request $request, GetMyBreederHandler $handler): JsonResponse
    {
        $breeder = $handler->handle($this->authenticatedUserId($request));

        return response()->json(['data' => $breeder ? new BreederResource($breeder) : null]);
    }

    public function pendingVerification(ListPendingBreederVerificationsHandler $handler): JsonResponse
    {
        Gate::authorize('verify-breeders');

        return response()->json(['data' => BreederResource::collection($handler->handle())]);
    }

    public function verify(string $breederId, Request $request, VerifyBreederHandler $handler): JsonResponse
    {
        Gate::authorize('verify-breeders');

        try {
            $breeder = $handler->handle(new VerifyBreederCommand(
                breederId: $breederId,
                moderatorUserId: $this->authenticatedUserId($request),
                approve: $request->boolean('approve'),
            ));
        } catch (BreederNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => new BreederResource($breeder)]);
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
