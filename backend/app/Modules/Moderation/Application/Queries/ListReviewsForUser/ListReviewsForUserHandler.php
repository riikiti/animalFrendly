<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\ListReviewsForUser;

use App\Modules\Moderation\Domain\Entities\Review;
use App\Modules\Moderation\Domain\Repositories\ReviewRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListReviewsForUserHandler
{
    public function __construct(private readonly ReviewRepositoryInterface $reviews) {}

    /**
     * @return list<Review>
     */
    public function handle(ListReviewsForUserQuery $query): array
    {
        return $this->reviews->findForTargetUser(Id::fromString($query->userId));
    }
}
