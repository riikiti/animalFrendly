<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Shared\Domain\ValueObjects\Id;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated access', function (): void {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
});

it('lists notifications and reports the unread count for the acting user', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $dispatcher = app(NotificationDispatcher::class);
    $dispatcher->notify(Id::fromString($user->id), NotificationType::NewMatch, 'У вас новый мэтч!');
    $dispatcher->notify(Id::fromString($user->id), NotificationType::NewMessage, 'Новое сообщение');
    $dispatcher->notify(Id::fromString($other->id), NotificationType::DealCompleted, 'Чужое уведомление');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 2);
});

it('marks a single notification as read and rejects marking someone else notification', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $dispatcher = app(NotificationDispatcher::class);
    $dispatcher->notify(Id::fromString($user->id), NotificationType::NewMatch, 'У вас новый мэтч!');

    Sanctum::actingAs($user);
    $notificationId = $this->getJson('/api/v1/notifications')->json('data.0.id');

    $this->postJson("/api/v1/notifications/{$notificationId}/read")
        ->assertOk()
        ->assertJsonPath('data.read_at', fn ($value) => $value !== null);

    $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 0);

    Sanctum::actingAs($other);
    $this->postJson("/api/v1/notifications/{$notificationId}/read")->assertForbidden();
});

it('marks all notifications as read', function (): void {
    $user = User::factory()->create();

    $dispatcher = app(NotificationDispatcher::class);
    $dispatcher->notify(Id::fromString($user->id), NotificationType::NewMatch, 'Первое');
    $dispatcher->notify(Id::fromString($user->id), NotificationType::NewMessage, 'Второе');

    Sanctum::actingAs($user);
    $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 2);

    $this->postJson('/api/v1/notifications/read-all')->assertOk();

    $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 0);
});
