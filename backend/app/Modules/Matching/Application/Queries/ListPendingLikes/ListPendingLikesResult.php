<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListPendingLikes;

use App\Modules\Profile\Domain\Entities\Pet;

final class ListPendingLikesResult
{
    /**
     * @param  list<Pet>  $received
     * @param  list<Pet>  $sent
     */
    public function __construct(
        public readonly array $received,
        public readonly array $sent,
    ) {}
}
