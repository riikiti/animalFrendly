<?php

declare(strict_types=1);

namespace App\Modules\Notification\Presentation\Http\Resources;

use App\Modules\Notification\Domain\Entities\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
final class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Notification $notification */
        $notification = $this->resource;

        return [
            'id' => $notification->id()->toString(),
            'type' => $notification->type()->value,
            'message' => $notification->message(),
            'data' => $notification->data(),
            'read_at' => $notification->readAt()?->format(DATE_ATOM),
            'created_at' => $notification->createdAt()->format(DATE_ATOM),
        ];
    }
}
