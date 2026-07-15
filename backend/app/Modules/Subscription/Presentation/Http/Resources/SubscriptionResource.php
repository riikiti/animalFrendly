<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Presentation\Http\Resources;

use App\Modules\Subscription\Domain\Entities\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
final class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Subscription $subscription */
        $subscription = $this->resource;

        return [
            'id' => $subscription->id()->toString(),
            'plan_id' => $subscription->planId(),
            'status' => $subscription->status()->value,
            'started_at' => $subscription->startedAt()?->format(DATE_ATOM),
            'current_period_ends_at' => $subscription->currentPeriodEndsAt()?->format(DATE_ATOM),
            'auto_renew' => $subscription->autoRenew(),
            'canceled_at' => $subscription->canceledAt()?->format(DATE_ATOM),
        ];
    }
}
