<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Moderation\Domain\Entities\Report as DomainReport;
use App\Modules\Moderation\Domain\Enums\ReportReason;
use App\Modules\Moderation\Domain\Enums\ReportStatus;
use App\Modules\Moderation\Domain\Enums\ReportTargetType;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;
use App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Models\Report as EloquentReport;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentReportRepository implements ReportRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainReport $report): void
    {
        EloquentReport::query()->updateOrCreate(
            ['id' => $report->id()->toString()],
            [
                'reporter_id' => $report->reporterId()->toString(),
                'target_type' => $report->targetType()->value,
                'target_id' => $report->targetId(),
                'reason' => $report->reason()->value,
                'comment' => $report->comment(),
                'status' => $report->status()->value,
                'reviewed_by' => $report->reviewedBy()?->toString(),
                'reviewed_at' => $report->reviewedAt(),
                'created_at' => $report->createdAt(),
            ],
        );
    }

    public function findById(Id $id): ?DomainReport
    {
        $model = EloquentReport::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findPending(int $limit = 50): array
    {
        return array_values(
            EloquentReport::query()
                ->where('status', ReportStatus::Pending->value)
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function countPending(): int
    {
        return EloquentReport::query()->where('status', ReportStatus::Pending->value)->count();
    }

    private function toDomain(EloquentReport $model): DomainReport
    {
        return DomainReport::reconstitute(
            id: Id::fromString($model->id),
            reporterId: Id::fromString($model->reporter_id),
            targetType: ReportTargetType::from($model->target_type),
            targetId: $model->target_id,
            reason: ReportReason::from($model->reason),
            comment: $model->comment,
            status: ReportStatus::from($model->status),
            reviewedBy: $model->reviewed_by !== null ? Id::fromString($model->reviewed_by) : null,
            reviewedAt: $model->reviewed_at?->toDateTimeImmutable(),
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
