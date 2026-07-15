<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\ListPendingReports;

use App\Modules\Moderation\Domain\Entities\Report;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;

final class ListPendingReportsHandler
{
    public function __construct(private readonly ReportRepositoryInterface $reports) {}

    /**
     * @return list<Report>
     */
    public function handle(ListPendingReportsQuery $query): array
    {
        return $this->reports->findPending($query->limit);
    }
}
