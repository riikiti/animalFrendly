<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Identity\Presentation\Http\Resources\UserResource;
use App\Modules\Moderation\Application\Commands\BanUser\BanUserCommand;
use App\Modules\Moderation\Application\Commands\BanUser\BanUserHandler;
use App\Modules\Moderation\Application\Commands\UnbanUser\UnbanUserCommand;
use App\Modules\Moderation\Application\Commands\UnbanUser\UnbanUserHandler;
use App\Modules\Moderation\Domain\Exceptions\UserNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class BanController
{
    public function ban(string $userId, Request $request, BanUserHandler $handler): JsonResponse
    {
        Gate::authorize('ban-users');

        try {
            $handler->handle(new BanUserCommand(
                userId: $userId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        $model = IdentityUser::query()->findOrFail($userId);

        return response()->json(['data' => new UserResource($model)]);
    }

    public function unban(string $userId, Request $request, UnbanUserHandler $handler): JsonResponse
    {
        Gate::authorize('ban-users');

        try {
            $handler->handle(new UnbanUserCommand(
                userId: $userId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        $model = IdentityUser::query()->findOrFail($userId);

        return response()->json(['data' => new UserResource($model)]);
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
