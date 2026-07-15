<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Presentation\Http\Resources;

use App\Modules\Subscription\Domain\Entities\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SubscriptionPlan
 */
final class SubscriptionPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SubscriptionPlan $plan */
        $plan = $this->resource;

        return [
            'id' => $plan->id(),
            'slug' => $plan->slug(),
            'name_ru' => $plan->nameRu(),
            'price_amount' => $plan->price()->minorUnits,
            'currency' => $plan->price()->currency,
            'period' => $plan->period()->value,
            'features' => $plan->features(),
        ];
    }
}
