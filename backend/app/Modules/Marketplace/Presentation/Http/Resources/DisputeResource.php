<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Resources;

use App\Modules\Marketplace\Domain\Entities\Dispute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Dispute
 */
final class DisputeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Dispute $dispute */
        $dispute = $this->resource;

        return [
            'id' => $dispute->id()->toString(),
            'order_id' => $dispute->orderId()->toString(),
            'opened_by' => $dispute->openedBy()->toString(),
            'reason' => $dispute->reason(),
            'resolution' => $dispute->resolution()?->value,
            'resolved_by' => $dispute->resolvedBy()?->toString(),
            'resolved_at' => $dispute->resolvedAt()?->format(DATE_ATOM),
        ];
    }
}
