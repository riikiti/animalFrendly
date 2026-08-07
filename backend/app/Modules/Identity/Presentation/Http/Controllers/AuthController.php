<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use App\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use App\Modules\Identity\Application\Commands\AuthenticateWithPhoneCode\AuthenticateWithPhoneCodeCommand;
use App\Modules\Identity\Application\Commands\AuthenticateWithPhoneCode\AuthenticateWithPhoneCodeHandler;
use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserHandler;
use App\Modules\Identity\Application\Commands\ResetPasswordWithPhoneCode\ResetPasswordWithPhoneCodeCommand;
use App\Modules\Identity\Application\Commands\ResetPasswordWithPhoneCode\ResetPasswordWithPhoneCodeHandler;
use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarCommand;
use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarHandler;
use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileCommand;
use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileHandler;
use App\Modules\Identity\Application\Services\PhoneCodeService;
use App\Modules\Identity\Domain\Enums\PhoneCodePurpose;
use App\Modules\Identity\Domain\Exceptions\AccountBlockedException;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Exceptions\InvalidPhoneCodeException;
use App\Modules\Identity\Domain\Exceptions\PhoneAlreadyRegisteredException;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;
use App\Modules\Identity\Presentation\Http\Requests\LoginRequest;
use App\Modules\Identity\Presentation\Http\Requests\PhoneCodeLoginRequest;
use App\Modules\Identity\Presentation\Http\Requests\RegisterRequest;
use App\Modules\Identity\Presentation\Http\Requests\RequestPhoneCodeRequest;
use App\Modules\Identity\Presentation\Http\Requests\ResetPasswordRequest;
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
                name: $request->string('name')->toString() ?: null,
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
            'token' => $this->issueToken($model, $request->boolean('remember')),
        ]);
    }

    /**
     * Отправляет код из СМС. Ответ одинаков независимо от того, есть ли такой номер —
     * иначе форма превращается в проверялку «зарегистрирован ли этот телефон».
     */
    public function requestPhoneCode(RequestPhoneCodeRequest $request, PhoneCodeService $codes): JsonResponse
    {
        $codes->issue(
            PhoneNumber::fromString($request->string('phone')->toString()),
            PhoneCodePurpose::from($request->string('purpose')->toString()),
        );

        return response()->json(['message' => 'Код отправлен.']);
    }

    public function loginWithPhoneCode(
        PhoneCodeLoginRequest $request,
        AuthenticateWithPhoneCodeHandler $handler,
    ): JsonResponse {
        try {
            $user = $handler->handle(new AuthenticateWithPhoneCodeCommand(
                phone: $request->string('phone')->toString(),
                code: $request->string('code')->toString(),
            ));
        } catch (InvalidPhoneCodeException|InvalidCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        } catch (AccountBlockedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $model = EloquentUser::query()->findOrFail($user->id()->toString());

        return response()->json([
            'user' => new UserResource($model),
            'token' => $this->issueToken($model, $request->boolean('remember')),
        ]);
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPasswordWithPhoneCodeHandler $handler,
    ): JsonResponse {
        try {
            $user = $handler->handle(new ResetPasswordWithPhoneCodeCommand(
                phone: $request->string('phone')->toString(),
                code: $request->string('code')->toString(),
                password: $request->string('password')->toString(),
            ));
        } catch (InvalidPhoneCodeException|InvalidCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        $model = EloquentUser::query()->findOrFail($user->id()->toString());

        return response()->json([
            'user' => new UserResource($model),
            'token' => $this->issueToken($model, false),
        ]);
    }

    /**
     * «Запомнить меня» продлевает жизнь токена: без галочки он живёт сутки, с ней — 90 дней.
     */
    private function issueToken(EloquentUser $user, bool $remember): string
    {
        return $user->createToken(
            'api',
            ['*'],
            $remember ? now()->addDays(90) : now()->addDay(),
        )->plainTextToken;
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
