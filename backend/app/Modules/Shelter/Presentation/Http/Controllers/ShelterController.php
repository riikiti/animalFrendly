<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Shelter\Application\Commands\RegisterShelter\RegisterShelterCommand;
use App\Modules\Shelter\Application\Commands\RegisterShelter\RegisterShelterHandler;
use App\Modules\Shelter\Application\Commands\VerifyShelter\VerifyShelterCommand;
use App\Modules\Shelter\Application\Commands\VerifyShelter\VerifyShelterHandler;
use App\Modules\Shelter\Application\Queries\GetMyShelter\GetMyShelterHandler;
use App\Modules\Shelter\Application\Queries\ListPendingShelterVerifications\ListPendingShelterVerificationsHandler;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Presentation\Http\Requests\StoreShelterRequest;
use App\Modules\Shelter\Presentation\Http\Requests\VerifyShelterRequest;
use App\Modules\Shelter\Presentation\Http\Resources\ShelterResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShelterController
{
    public function store(StoreShelterRequest $request, RegisterShelterHandler $handler): JsonResponse
    {
        $shelter = $handler->handle(new RegisterShelterCommand(
            ownerUserId: $this->authenticatedUserId($request),
            legalName: $request->string('legal_name')->toString(),
            inn: $request->string('inn')->toString() ?: null,
            description: $request->string('description')->toString() ?: null,
        ));

        return response()->json(['data' => new ShelterResource($shelter)], 201);
    }

    public function me(Request $request, GetMyShelterHandler $handler): JsonResponse
    {
        $shelter = $handler->handle($this->authenticatedUserId($request));

        return response()->json(['data' => $shelter ? new ShelterResource($shelter) : null]);
    }

    public function pendingVerification(Request $request, ListPendingShelterVerificationsHandler $handler): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! in_array($user->account_type, ['moderator', 'admin'], true)) {
            abort(403, 'Доступно только модераторам.');
        }

        return response()->json(['data' => ShelterResource::collection($handler->handle())]);
    }

    public function verify(string $shelterId, VerifyShelterRequest $request, VerifyShelterHandler $handler): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        if (! in_array($user->account_type, ['moderator', 'admin'], true)) {
            abort(403, 'Верификация доступна только модераторам.');
        }

        try {
            $shelter = $handler->handle(new VerifyShelterCommand(
                shelterId: $shelterId,
                moderatorUserId: $user->id,
                approve: $request->boolean('approve'),
            ));
        } catch (ShelterNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => new ShelterResource($shelter)]);
    }

    private function authenticatedUserId(Request $request): string
    {
        return $this->authenticatedUser($request)->id;
    }

    private function authenticatedUser(Request $request): IdentityUser
    {
        $user = $request->user();

        if (! $user instanceof IdentityUser) {
            abort(401);
        }

        return $user;
    }
}
