<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Infrastructure\Adapters;

use App\Modules\Moderation\Domain\Entities\AuditLog;
use App\Modules\Moderation\Domain\Repositories\AuditLogRepositoryInterface;
use App\Shared\Application\AuditLogWriterInterface;
use App\Shared\Domain\ValueObjects\Id;

final class AuditLogWriter implements AuditLogWriterInterface
{
    public function __construct(private readonly AuditLogRepositoryInterface $auditLogs) {}

    public function record(?Id $actorId, string $action, string $entityType, string $entityId, array $payload = []): void
    {
        $log = AuditLog::create(
            id: $this->auditLogs->nextIdentity(),
            actorId: $actorId,
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            payload: $payload,
        );

        $this->auditLogs->save($log);
    }
}
