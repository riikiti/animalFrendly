<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Resources;

use App\Modules\Moderation\Domain\Entities\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
final class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Review $review */
        $review = $this->resource;

        return [
            'id' => $review->id()->toString(),
            'order_id' => $review->orderId()?->toString(),
            'adoption_request_id' => $review->adoptionRequestId()?->toString(),
            'author_id' => $review->authorId()->toString(),
            'target_user_id' => $review->targetUserId()->toString(),
            'rating' => $review->rating(),
            'comment' => $review->comment(),
            'created_at' => $review->createdAt()->format(DATE_ATOM),
        ];
    }
}
