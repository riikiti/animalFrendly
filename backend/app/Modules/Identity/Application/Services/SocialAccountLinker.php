<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Services;

use App\Modules\Identity\Domain\Exceptions\SocialAccountNotLinkedException;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\SocialAccount;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;

/**
 * Связывает аккаунт внешнего провайдера с пользователем.
 *
 * Аккаунт в приложении держится на телефоне: он нужен и для входа, и для расстояний,
 * и для чата. Поэтому вход через провайдера не создаёт нового пользователя, а находит
 * существующего — по ранее сделанной привязке или по совпадению почты — и привязывается
 * к нему. Если такого пользователя нет, зовём сначала зарегистрироваться по телефону.
 */
final class SocialAccountLinker
{
    public function resolve(string $provider, string $providerUserId, ?string $email): User
    {
        $existing = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($existing !== null) {
            return $existing->user()->firstOrFail();
        }

        $user = $email === null
            ? null
            : User::query()->where('email', $email)->first();

        if ($user === null) {
            throw SocialAccountNotLinkedException::create();
        }

        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'email' => $email,
        ]);

        return $user;
    }

    /**
     * Привязка к уже вошедшему пользователю — из настроек профиля.
     */
    public function link(User $user, string $provider, string $providerUserId, ?string $email): void
    {
        SocialAccount::query()->updateOrCreate(
            ['provider' => $provider, 'provider_user_id' => $providerUserId],
            ['user_id' => $user->id, 'email' => $email],
        );
    }
}
