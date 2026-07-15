<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\SubmitReport;

use App\Modules\Moderation\Domain\Entities\Report;
use App\Modules\Moderation\Domain\Enums\ReportReason;
use App\Modules\Moderation\Domain\Enums\ReportTargetType;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class SubmitReportHandler
{
    public function __construct(private readonly ReportRepositoryInterface $reports) {}

    public function handle(SubmitReportCommand $command): Report
    {
        $report = Report::create(
            id: $this->reports->nextIdentity(),
            reporterId: Id::fromString($command->reporterId),
            targetType: ReportTargetType::from($command->targetType),
            targetId: $command->targetId,
            reason: ReportReason::from($command->reason),
            comment: $command->comment,
        );

        $this->reports->save($report);

        return $report;
    }
}
