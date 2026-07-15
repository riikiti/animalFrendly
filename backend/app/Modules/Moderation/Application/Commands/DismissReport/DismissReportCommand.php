<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\DismissReport;

final class DismissReportCommand
{
    public function __construct(
        public readonly string $reportId,
        public readonly string $actingUserId,
    ) {}
}
