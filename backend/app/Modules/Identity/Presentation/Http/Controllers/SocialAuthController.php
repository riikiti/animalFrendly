<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Application\Services\SocialAccountLinker;
use App\Modules\Identity\Domain\Exceptions\SocialAccountNotLinkedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

/**
 * Вход через внешних провайдеров. Кнопки на фронтенде рисуются только для тех,
 * у кого заданы ключи, — список отдаёт providers().
 */
final class SocialAuthController
{
    private const SUPPORTED = ['google', 'vkontakte'];

    /**
     * @return array<int, string>
     */
    private function enabled(): array
    {
        return array_values(array_filter(
            self::SUPPORTED,
            static fn (string $provider): bool => Config::string("services.{$provider}.client_id", '') !== '',
        ));
    }

    /**
     * stateless() живёт в реализации OAuth 2, а не в общем контракте Socialite,
     * поэтому драйвер сужаем явно.
     */
    private function oauthDriver(string $provider): AbstractProvider
    {
        $driver = Socialite::driver($provider);

        if (! $driver instanceof AbstractProvider) {
            abort(500, 'Провайдер не поддерживает OAuth 2.');
        }

        return $driver;
    }

    public function providers(): JsonResponse
    {
        return response()->json(['data' => $this->enabled()]);
    }

    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, $this->enabled(), true)) {
            return response()->json(['message' => 'Провайдер не подключён.'], 404);
        }

        $driver = $this->oauthDriver($provider);

        return $driver->stateless()->redirect();
    }

    public function callback(string $provider, SocialAccountLinker $linker): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, $this->enabled(), true)) {
            return response()->json(['message' => 'Провайдер не подключён.'], 404);
        }

        $account = $this->oauthDriver($provider)->stateless()->user();

        try {
            $user = $linker->resolve($provider, (string) $account->getId(), $account->getEmail());
        } catch (SocialAccountNotLinkedException $e) {
            return redirect()->away(
                Config::string('app.frontend_url').'/login?social_error='.urlencode($e->getMessage()),
            );
        }

        // Токен уезжает во фронтенд параметром — там он сразу перекладывается в хранилище
        // и вычищается из адресной строки (см. LoginPage.vue).
        $token = $user->createToken('api', ['*'], now()->addDays(90))->plainTextToken;

        return redirect()->away(Config::string('app.frontend_url').'/login?social_token='.$token);
    }
}
