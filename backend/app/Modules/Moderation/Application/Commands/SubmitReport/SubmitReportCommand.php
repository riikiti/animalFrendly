<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\SubmitReport;

final class SubmitReportCommand
{
    public function __construct(
        public readonly string $reporterId,
        public readonly string $targetType,
        public readonly string $targetId,
        public readonly string $reason,
        public readonly ?string $comment,
    ) {}
}
