<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\ListPendingReports;

final class ListPendingReportsQuery
{
    public function __construct(public readonly int $limit = 50) {}
}
