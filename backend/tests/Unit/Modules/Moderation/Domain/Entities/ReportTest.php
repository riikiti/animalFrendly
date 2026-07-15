<?php

declare(strict_types=1);

use App\Modules\Moderation\Domain\Entities\Report;
use App\Modules\Moderation\Domain\Enums\ReportReason;
use App\Modules\Moderation\Domain\Enums\ReportStatus;
use App\Modules\Moderation\Domain\Enums\ReportTargetType;
use App\Modules\Moderation\Domain\Exceptions\InvalidReportStatusTransitionException;
use App\Shared\Domain\ValueObjects\Id;

function makeTestReport(): Report
{
    return Report::create(
        id: Id::generate(),
        reporterId: Id::generate(),
        targetType: ReportTargetType::Pet,
        targetId: Id::generate()->toString(),
        reason: ReportReason::Spam,
        comment: 'Похоже на спам',
    );
}

it('starts pending', function (): void {
    $report = makeTestReport();

    expect($report->status())->toBe(ReportStatus::Pending)
        ->and($report->reviewedBy())->toBeNull();
});

it('marks a pending report as reviewed', function (): void {
    $report = makeTestReport();
    $moderatorId = Id::generate();

    $report->markReviewed($moderatorId);

    expect($report->status())->toBe(ReportStatus::Reviewed)
        ->and($report->reviewedBy()->equals($moderatorId))->toBeTrue()
        ->and($report->reviewedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('dismisses a pending report', function (): void {
    $report = makeTestReport();
    $moderatorId = Id::generate();

    $report->dismiss($moderatorId);

    expect($report->status())->toBe(ReportStatus::Dismissed);
});

it('rejects reviewing an already-reviewed report', function (): void {
    $report = makeTestReport();
    $report->markReviewed(Id::generate());

    $report->markReviewed(Id::generate());
})->throws(InvalidReportStatusTransitionException::class);

it('rejects dismissing an already-dismissed report', function (): void {
    $report = makeTestReport();
    $report->dismiss(Id::generate());

    $report->dismiss(Id::generate());
})->throws(InvalidReportStatusTransitionException::class);
