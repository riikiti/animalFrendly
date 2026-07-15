<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\ListRecentAuditLog;

use App\Modules\Moderation\Domain\Entities\AuditLog;
use App\Modules\Moderation\Domain\Repositories\AuditLogRepositoryInterface;

final class ListRecentAuditLogHandler
{
    public function __construct(private readonly AuditLogRepositoryInterface $auditLog) {}

    /**
     * @return list<AuditLog>
     */
    public function handle(ListRecentAuditLogQuery $query): array
    {
        return $this->auditLog->findRecent($query->limit);
    }
}
