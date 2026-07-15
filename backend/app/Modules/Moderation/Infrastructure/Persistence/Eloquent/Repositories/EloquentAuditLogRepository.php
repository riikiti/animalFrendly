<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Moderation\Domain\Entities\AuditLog as DomainAuditLog;
use App\Modules\Moderation\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Models\AuditLog as EloquentAuditLog;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainAuditLog $log): void
    {
        EloquentAuditLog::query()->updateOrCreate(
            ['id' => $log->id()->toString()],
            [
                'actor_id' => $log->actorId()?->toString(),
                'action' => $log->action(),
                'entity_type' => $log->entityType(),
                'entity_id' => $log->entityId(),
                'payload' => $log->payload(),
                'created_at' => $log->createdAt(),
            ],
        );
    }

    public function findRecent(int $limit = 50): array
    {
        return array_values(
            EloquentAuditLog::query()
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentAuditLog $model): DomainAuditLog
    {
        return DomainAuditLog::reconstitute(
            id: Id::fromString($model->id),
            actorId: $model->actor_id !== null ? Id::fromString($model->actor_id) : null,
            action: $model->action,
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            payload: $model->payload,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
