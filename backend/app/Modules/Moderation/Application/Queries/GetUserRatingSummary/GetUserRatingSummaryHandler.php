<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\GetUserRatingSummary;

use App\Modules\Moderation\Domain\Repositories\ReviewRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetUserRatingSummaryHandler
{
    public function __construct(private readonly ReviewRepositoryInterface $reviews) {}

    /**
     * @return array{average: float, count: int}
     */
    public function handle(GetUserRatingSummaryQuery $query): array
    {
        return $this->reviews->ratingSummaryForUser(Id::fromString($query->userId));
    }
}
