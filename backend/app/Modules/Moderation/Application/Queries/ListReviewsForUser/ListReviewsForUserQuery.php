<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\ListReviewsForUser;

final class ListReviewsForUserQuery
{
    public function __construct(public readonly string $userId) {}
}
