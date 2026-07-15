<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\External;

use App\Modules\Notification\Application\Contracts\FcmClientInterface;
use App\Modules\Notification\Domain\Exceptions\FcmRequestFailedException;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * FCM HTTP v1 API (https://firebase.google.com/docs/cloud-messaging/send-message).
 * Аутентификация — OAuth2 JWT-bearer grant сервис-аккаунта (FCM_CREDENTIALS_PATH),
 * access-токен кешируется на чуть меньше часа. Реальных креденшлов ещё нет — рабочий
 * адаптер, конфигурируется через config/fcm.php, тот же принцип, что YookassaClient.
 */
final class FcmClient implements FcmClientInterface
{
    public function send(string $token, string $title, string $body, array $data = []): void
    {
        $project = (string) config('fcm.project_id');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map(static fn (mixed $v): string => (string) $v, $data),
                ],
            ]);

        if ($response->failed()) {
            throw FcmRequestFailedException::create('send', $response->body());
        }
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm.access_token', 3500, function (): string {
            $credentials = json_decode(
                (string) file_get_contents((string) config('fcm.credentials_path')),
                true,
            );

            $now = time();
            $jwt = JWT::encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], (string) $credentials['private_key'], 'RS256');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                throw FcmRequestFailedException::create('token', $response->body());
            }

            return (string) $response->json('access_token');
        });
    }
}
