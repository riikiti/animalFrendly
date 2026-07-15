<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\ListRecentAuditLog;

final class ListRecentAuditLogQuery
{
    public function __construct(public readonly int $limit = 50) {}
}
