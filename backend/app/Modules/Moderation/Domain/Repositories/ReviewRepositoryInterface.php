<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Repositories;

use App\Modules\Moderation\Domain\Entities\Review;
use App\Shared\Domain\ValueObjects\Id;

interface ReviewRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Review $review): void;

    public function existsForAuthorAndOrder(Id $authorId, Id $orderId): bool;

    public function existsForAuthorAndAdoptionRequest(Id $authorId, Id $adoptionRequestId): bool;

    /**
     * @return list<Review>
     */
    public function findForTargetUser(Id $userId): array;

    /**
     * @return array{average: float, count: int}
     */
    public function ratingSummaryForUser(Id $userId): array;
}
