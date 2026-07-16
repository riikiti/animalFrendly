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
 *
 * Переключение вынесено из beforeEach в отдельный хелпер, вызываемый только непосредственно
 * перед запросом на /api/broadcasting/auth — createMutualMatch() внутри теста создаёт мэтч,
 * что теперь (см. NotificationDispatcher::notify()) само по себе вызывает broadcast()
 * уведомления о мэтче; если к этому моменту соединение уже 'reverb', этот побочный broadcast
 * пытается достучаться до реального Reverb-сервера по сети и падает, хотя тест интересует
 * только последующая проверка авторизации канала.
 */
function switchBroadcastConnectionToReverb(): void
{
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');
}

it('authorizes a match participant on the private conversation channel', function (): void {
    [$ownerA, , $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $conversationId = $this->getJson("/api/v1/matches/{$matchId}/conversation")->json('data.id');

    switchBroadcastConnectionToReverb();

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
    switchBroadcastConnectionToReverb();

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => "private-conversation.{$conversationId}",
    ])->assertForbidden();
});

it('rejects unauthenticated access to the broadcasting auth endpoint', function (): void {
    switchBroadcastConnectionToReverb();

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-conversation.does-not-matter',
    ])->assertUnauthorized();
});
