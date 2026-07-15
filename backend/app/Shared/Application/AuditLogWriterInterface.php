<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Shared\Domain\ValueObjects\Id;

/**
 * Fan-in контракт (многие модули потенциально пишут в журнал) — объявлен в Shared, а не в
 * Moderation, тот же принцип, что DomainEventDispatcherInterface. Реализация —
 * Moderation\Infrastructure\Adapters\AuditLogWriter, байндится в ModerationServiceProvider.
 */
interface AuditLogWriterInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(?Id $actorId, string $action, string $entityType, string $entityId, array $payload = []): void;
}
