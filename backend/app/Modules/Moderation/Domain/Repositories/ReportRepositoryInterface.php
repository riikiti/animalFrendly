<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Repositories;

use App\Modules\Moderation\Domain\Entities\Report;
use App\Shared\Domain\ValueObjects\Id;

interface ReportRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Report $report): void;

    public function findById(Id $id): ?Report;

    /**
     * @return list<Report>
     */
    public function findPending(int $limit = 50): array;

    public function countPending(): int;
}
