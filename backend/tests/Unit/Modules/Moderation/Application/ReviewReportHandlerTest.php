<?php

declare(strict_types=1);

use App\Modules\Moderation\Application\Commands\ReviewReport\ReviewReportCommand;
use App\Modules\Moderation\Application\Commands\ReviewReport\ReviewReportHandler;
use App\Modules\Moderation\Domain\Entities\Report;
use App\Modules\Moderation\Domain\Enums\ReportReason;
use App\Modules\Moderation\Domain\Enums\ReportTargetType;
use App\Modules\Moderation\Domain\Exceptions\ReportNotFoundException;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;
use App\Shared\Application\AuditLogWriterInterface;
use App\Shared\Domain\ValueObjects\Id;

it('marks a report reviewed and writes an audit log entry', function (): void {
    $reportId = Id::generate();
    $moderatorId = Id::generate();
    $report = Report::create($reportId, Id::generate(), ReportTargetType::Pet, Id::generate()->toString(), ReportReason::Spam, null);

    $reports = Mockery::mock(ReportRepositoryInterface::class);
    $reports->shouldReceive('findById')->once()->andReturn($report);
    $reports->shouldReceive('save')->once()->with(Mockery::on(fn (Report $r) => $r->status()->value === 'reviewed'));

    $auditLog = Mockery::mock(AuditLogWriterInterface::class);
    $auditLog->shouldReceive('record')->once()->with(
        Mockery::on(fn (Id $id) => $id->equals($moderatorId)),
        'report.reviewed',
        'report',
        $reportId->toString(),
    );

    $handler = new ReviewReportHandler($reports, $auditLog);
    $handler->handle(new ReviewReportCommand($reportId->toString(), $moderatorId->toString()));
});

it('rejects reviewing a report that does not exist', function (): void {
    $reports = Mockery::mock(ReportRepositoryInterface::class);
    $reports->shouldReceive('findById')->once()->andReturn(null);

    $auditLog = Mockery::mock(AuditLogWriterInterface::class);
    $auditLog->shouldNotReceive('record');

    $handler = new ReviewReportHandler($reports, $auditLog);
    $handler->handle(new ReviewReportCommand(Id::generate()->toString(), Id::generate()->toString()));
})->throws(ReportNotFoundException::class);
