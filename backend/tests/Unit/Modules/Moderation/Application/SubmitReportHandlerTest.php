<?php

declare(strict_types=1);

use App\Modules\Moderation\Application\Commands\SubmitReport\SubmitReportCommand;
use App\Modules\Moderation\Application\Commands\SubmitReport\SubmitReportHandler;
use App\Modules\Moderation\Domain\Entities\Report;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('creates a pending report', function (): void {
    $reporterId = Id::generate();

    $reports = Mockery::mock(ReportRepositoryInterface::class);
    $reports->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $reports->shouldReceive('save')->once()->with(Mockery::on(
        fn (Report $r) => $r->reporterId()->equals($reporterId) && $r->targetType()->value === 'pet',
    ));

    $handler = new SubmitReportHandler($reports);
    $report = $handler->handle(new SubmitReportCommand(
        reporterId: $reporterId->toString(),
        targetType: 'pet',
        targetId: Id::generate()->toString(),
        reason: 'spam',
        comment: null,
    ));

    expect($report->status()->value)->toBe('pending');
});
