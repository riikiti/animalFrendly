<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use App\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserHandler;
use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarCommand;
use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarHandler;
use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileCommand;
use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileHandler;
use App\Modules\Identity\Domain\Exceptions\AccountBlockedException;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Exceptions\PhoneAlreadyRegisteredException;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;
use App\Modules\Identity\Presentation\Http\Requests\LoginRequest;
use App\Modules\Identity\Presentation\Http\Requests\RegisterRequest;
use App\Modules\Identity\Presentation\Http\Requests\UpdateAvatarRequest;
use App\Modules\Identity\Presentation\Http\Requests\UpdateProfileRequest;
use App\Modules\Identity\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class AuthController
{
    public function register(RegisterRequest $request, RegisterUserHandler $handler): JsonResponse
    {
        try {
            // Регистрация двухшаговая: шаг 1 создаёт обычный аккаунт без выбора роли, режим
            // (приют/заводчик) пользователь выбирает отдельно после неё — см. OnboardingModePage.
            $user = $handler->handle(new RegisterUserCommand(
                phone: $request->string('phone')->toString(),
                password: $request->string('password')->toString(),
                accountType: 'owner',
                personalDataConsentGiven: $request->boolean('personal_data_consent'),
            ));
        } catch (PhoneAlreadyRegisteredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $model = EloquentUser::query()->findOrFail($user->id()->toString());

        return response()->json([
            'user' => new UserResource($model),
            'token' => $model->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request, AuthenticateUserHandler $handler): JsonResponse
    {
        try {
            $user = $handler->handle(new AuthenticateUserCommand(
                phone: $request->string('phone')->toString(),
                password: $request->string('password')->toString(),
            ));
        } catch (InvalidCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        } catch (AccountBlockedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $model = EloquentUser::query()->findOrFail($user->id()->toString());

        return response()->json([
            'user' => new UserResource($model),
            'token' => $model->createToken('api')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof EloquentUser) {
            abort(401);
        }

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'ok']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function updateProfile(
        UpdateProfileRequest $request,
        UpdateProfileHandler $handler,
    ): UserResource {
        $user = $request->user();

        if (! $user instanceof EloquentUser) {
            abort(401);
        }

        $handler->handle(new UpdateProfileCommand(
            userId: $user->id,
            name: $request->string('name')->toString() ?: null,
            address: $request->string('address')->toString() ?: null,
        ));

        return new UserResource($user->fresh());
    }

    public function uploadAvatar(UpdateAvatarRequest $request, UpdateAvatarHandler $handler): UserResource
    {
        $user = $request->user();

        if (! $user instanceof EloquentUser) {
            abort(401);
        }

        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            abort(422, 'Файл не передан.');
        }

        $handler->handle(new UpdateAvatarCommand(userId: $user->id, photo: $photo));

        return new UserResource($user->fresh());
    }
}
