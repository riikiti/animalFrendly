<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\ReviewReport;

use App\Modules\Moderation\Domain\Entities\Report;
use App\Modules\Moderation\Domain\Exceptions\ReportNotFoundException;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;
use App\Shared\Application\AuditLogWriterInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ReviewReportHandler
{
    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly AuditLogWriterInterface $auditLog,
    ) {}

    public function handle(ReviewReportCommand $command): Report
    {
        $report = $this->reports->findById(Id::fromString($command->reportId));

        if ($report === null) {
            throw ReportNotFoundException::forId($command->reportId);
        }

        $actingUserId = Id::fromString($command->actingUserId);
        $report->markReviewed($actingUserId);
        $this->reports->save($report);

        $this->auditLog->record($actingUserId, 'report.reviewed', 'report', $command->reportId);

        return $report;
    }
}
