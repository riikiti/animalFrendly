<?php

declare(strict_types=1);

namespace App\Modules\Media\Domain\Repositories;

use App\Modules\Media\Domain\Entities\Media;
use App\Shared\Domain\ValueObjects\Id;

interface MediaRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Media $media): void;

    public function findById(Id $id): ?Media;
}
