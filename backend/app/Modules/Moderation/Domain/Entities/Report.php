<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Entities;

use App\Modules\Moderation\Domain\Enums\ReportReason;
use App\Modules\Moderation\Domain\Enums\ReportStatus;
use App\Modules\Moderation\Domain\Enums\ReportTargetType;
use App\Modules\Moderation\Domain\Exceptions\InvalidReportStatusTransitionException;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Report
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $reporterId,
        private readonly ReportTargetType $targetType,
        private readonly string $targetId,
        private readonly ReportReason $reason,
        private readonly ?string $comment,
        private ReportStatus $status,
        private ?Id $reviewedBy,
        private ?DateTimeImmutable $reviewedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        Id $id,
        Id $reporterId,
        ReportTargetType $targetType,
        string $targetId,
        ReportReason $reason,
        ?string $comment,
    ): self {
        return new self($id, $reporterId, $targetType, $targetId, $reason, $comment, ReportStatus::Pending, null, null, new DateTimeImmutable);
    }

    public static function reconstitute(
        Id $id,
        Id $reporterId,
        ReportTargetType $targetType,
        string $targetId,
        ReportReason $reason,
        ?string $comment,
        ReportStatus $status,
        ?Id $reviewedBy,
        ?DateTimeImmutable $reviewedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $reporterId, $targetType, $targetId, $reason, $comment, $status, $reviewedBy, $reviewedAt, $createdAt);
    }

    public function markReviewed(Id $reviewedBy): void
    {
        $this->assertPending();
        $this->status = ReportStatus::Reviewed;
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable;
    }

    public function dismiss(Id $reviewedBy): void
    {
        $this->assertPending();
        $this->status = ReportStatus::Dismissed;
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable;
    }

    private function assertPending(): void
    {
        if ($this->status !== ReportStatus::Pending) {
            throw InvalidReportStatusTransitionException::create();
        }
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function reporterId(): Id
    {
        return $this->reporterId;
    }

    public function targetType(): ReportTargetType
    {
        return $this->targetType;
    }

    public function targetId(): string
    {
        return $this->targetId;
    }

    public function reason(): ReportReason
    {
        return $this->reason;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function status(): ReportStatus
    {
        return $this->status;
    }

    public function reviewedBy(): ?Id
    {
        return $this->reviewedBy;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
