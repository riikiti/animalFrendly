<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Resources;

use App\Modules\Moderation\Domain\Entities\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
final class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AuditLog $log */
        $log = $this->resource;

        return [
            'id' => $log->id()->toString(),
            'actor_id' => $log->actorId()?->toString(),
            'action' => $log->action(),
            'entity_type' => $log->entityType(),
            'entity_id' => $log->entityId(),
            'payload' => $log->payload(),
            'created_at' => $log->createdAt()->format(DATE_ATOM),
        ];
    }
}
