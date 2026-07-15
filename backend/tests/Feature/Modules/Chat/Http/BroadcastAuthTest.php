<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Тестовый набор в целом гоняется с BROADCAST_CONNECTION=null (см. phpunit.xml) — безопасный
 * no-op-драйвер, который не бьёт по сети и не проверяет каналы (важно для остальных тестов,
 * отправляющих сообщения без интереса к broadcasting). Именно этому файлу нужна настоящая
 * проверка канала — реверб/pusher-драйвер её делает без реального сервера (Reverb переиспользует
 * PusherBroadcaster, а подпись авторизации канала считается локально через HMAC).
 *
 * routes/channels.php уже был подключён при загрузке приложения на дефолтном ('null')
 * соединении — Broadcast::channel() регистрирует паттерн на КОНКРЕТНОМ закэшированном
 * экземпляре драйвера (BroadcastManager::driver() кэширует по имени соединения), поэтому
 * простой смены config('broadcasting.default') недостаточно: нужно заново подключить
 * channels.php уже после переключения на 'reverb', чтобы регистрация попала на свежий
 * (первый резолв) экземпляр pusher-совместимого драйвера.
 */
beforeEach(function (): void {
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');
});

it('authorizes a match participant on the private conversation channel', function (): void {
    [$ownerA, , $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $conversationId = $this->getJson("/api/v1/matches/{$matchId}/conversation")->json('data.id');

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => "private-conversation.{$conversationId}",
    ])->assertOk();
});

it('rejects a non-participant on the private conversation channel', function (): void {
    [$ownerA, , $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $conversationId = $this->getJson("/api/v1/matches/{$matchId}/conversation")->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => "private-conversation.{$conversationId}",
    ])->assertForbidden();
});

it('rejects unauthenticated access to the broadcasting auth endpoint', function (): void {
    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-conversation.does-not-matter',
    ])->assertUnauthorized();
});
