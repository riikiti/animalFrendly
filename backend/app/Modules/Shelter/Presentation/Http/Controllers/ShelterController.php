<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Shelter\Application\Commands\RegisterShelter\RegisterShelterCommand;
use App\Modules\Shelter\Application\Commands\RegisterShelter\RegisterShelterHandler;
use App\Modules\Shelter\Application\Commands\UpdateShelter\UpdateShelterCommand;
use App\Modules\Shelter\Application\Commands\UpdateShelter\UpdateShelterHandler;
use App\Modules\Shelter\Application\Commands\UpdateShelterPhoto\UpdateShelterPhotoCommand;
use App\Modules\Shelter\Application\Commands\UpdateShelterPhoto\UpdateShelterPhotoHandler;
use App\Modules\Shelter\Application\Commands\VerifyShelter\VerifyShelterCommand;
use App\Modules\Shelter\Application\Commands\VerifyShelter\VerifyShelterHandler;
use App\Modules\Shelter\Application\Queries\GetMyShelter\GetMyShelterHandler;
use App\Modules\Shelter\Application\Queries\GetShelter\GetShelterHandler;
use App\Modules\Shelter\Application\Queries\GetShelter\GetShelterQuery;
use App\Modules\Shelter\Application\Queries\ListPendingShelterVerifications\ListPendingShelterVerificationsHandler;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Presentation\Http\Requests\StoreShelterRequest;
use App\Modules\Shelter\Presentation\Http\Requests\UpdateShelterPhotoRequest;
use App\Modules\Shelter\Presentation\Http\Requests\UpdateShelterRequest;
use App\Modules\Shelter\Presentation\Http\Requests\VerifyShelterRequest;
use App\Modules\Shelter\Presentation\Http\Resources\ShelterResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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

    public function show(string $shelterId, Request $request, GetShelterHandler $handler): JsonResponse
    {
        $shelter = $handler->handle(new GetShelterQuery($shelterId));

        if ($shelter === null) {
            return response()->json(['message' => "Приют «{$shelterId}» не найден."], 404);
        }

        $isOwner = $shelter->ownerUserId()->toString() === $this->authenticatedUserId($request);

        // Непроверенные приюты не раскрываем посторонним — ни факт существования, ни контакты
        // владельца (публичны только после модерации).
        if (! $shelter->isVerified() && ! $isOwner) {
            return response()->json(['message' => "Приют «{$shelterId}» не найден."], 404);
        }

        $owner = $shelter->isVerified()
            ? IdentityUser::query()->find($shelter->ownerUserId()->toString())
            : null;

        return response()->json(['data' => new ShelterResource(['shelter' => $shelter, 'owner' => $owner])]);
    }

    public function update(string $shelterId, UpdateShelterRequest $request, UpdateShelterHandler $handler): JsonResponse
    {
        try {
            $shelter = $handler->handle(new UpdateShelterCommand(
                shelterId: $shelterId,
                actingUserId: $this->authenticatedUserId($request),
                phone: $request->string('phone')->toString() ?: null,
                email: $request->string('email')->toString() ?: null,
                address: $request->string('address')->toString() ?: null,
            ));
        } catch (ShelterNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotShelterOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => new ShelterResource($shelter)]);
    }

    public function photo(string $shelterId, UpdateShelterPhotoRequest $request, UpdateShelterPhotoHandler $handler): JsonResponse
    {
        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            return response()->json(['message' => 'Файл не передан.'], 422);
        }

        try {
            $shelter = $handler->handle(new UpdateShelterPhotoCommand(
                shelterId: $shelterId,
                actingUserId: $this->authenticatedUserId($request),
                photo: $photo,
            ));
        } catch (ShelterNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotShelterOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => new ShelterResource($shelter)]);
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
