<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Repositories;

use App\Modules\Moderation\Domain\Entities\AuditLog;
use App\Shared\Domain\ValueObjects\Id;

interface AuditLogRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(AuditLog $log): void;

    /**
     * @return list<AuditLog>
     */
    public function findRecent(int $limit = 50): array;
}
