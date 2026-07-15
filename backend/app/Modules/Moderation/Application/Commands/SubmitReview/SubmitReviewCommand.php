<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\SubmitReview;

final class SubmitReviewCommand
{
    public function __construct(
        public readonly string $authorId,
        public readonly ?string $orderId,
        public readonly ?string $adoptionRequestId,
        public readonly int $rating,
        public readonly ?string $comment,
    ) {}
}
