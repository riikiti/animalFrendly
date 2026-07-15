<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Resources;

use App\Modules\Moderation\Domain\Entities\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
final class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Report $report */
        $report = $this->resource;

        return [
            'id' => $report->id()->toString(),
            'reporter_id' => $report->reporterId()->toString(),
            'target_type' => $report->targetType()->value,
            'target_id' => $report->targetId(),
            'reason' => $report->reason()->value,
            'comment' => $report->comment(),
            'status' => $report->status()->value,
            'reviewed_by' => $report->reviewedBy()?->toString(),
            'reviewed_at' => $report->reviewedAt()?->format(DATE_ATOM),
            'created_at' => $report->createdAt()->format(DATE_ATOM),
        ];
    }
}
