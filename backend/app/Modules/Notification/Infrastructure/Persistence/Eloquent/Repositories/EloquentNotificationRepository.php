<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Notification\Domain\Entities\Notification as DomainNotification;
use App\Modules\Notification\Domain\Enums\NotificationChannel;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\Notification as EloquentNotification;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainNotification $notification): void
    {
        EloquentNotification::query()->updateOrCreate(
            ['id' => $notification->id()->toString()],
            [
                'user_id' => $notification->userId()->toString(),
                'type' => $notification->type()->value,
                'payload' => ['message' => $notification->message(), ...$notification->data()],
                'channel' => $notification->channel()->value,
                'read_at' => $notification->readAt(),
                'created_at' => $notification->createdAt(),
            ],
        );
    }

    public function findById(Id $id): ?DomainNotification
    {
        $model = EloquentNotification::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUser(Id $userId, int $limit): array
    {
        return array_values(
            EloquentNotification::query()
                ->where('user_id', $userId->toString())
                // Сортировка по ULID, а не created_at — колонка имеет точность до секунды,
                // а ULID монотонен до миллисекунды, что важно при нескольких уведомлениях
                // в одной транзакции (например, оба участника мэтча).
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function countUnreadForUser(Id $userId): int
    {
        return EloquentNotification::query()
            ->where('user_id', $userId->toString())
            ->whereNull('read_at')
            ->count();
    }

    public function markAllReadForUser(Id $userId): void
    {
        EloquentNotification::query()
            ->where('user_id', $userId->toString())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function toDomain(EloquentNotification $model): DomainNotification
    {
        $payload = $model->payload;
        $message = (string) ($payload['message'] ?? '');
        unset($payload['message']);

        return DomainNotification::reconstitute(
            id: Id::fromString($model->id),
            userId: Id::fromString($model->user_id),
            type: NotificationType::from($model->type),
            message: $message,
            data: $payload,
            channel: NotificationChannel::from($model->channel),
            readAt: $model->read_at?->toDateTimeImmutable(),
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
