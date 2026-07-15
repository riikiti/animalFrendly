<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Queries\GetUserRatingSummary;

final class GetUserRatingSummaryQuery
{
    public function __construct(public readonly string $userId) {}
}
