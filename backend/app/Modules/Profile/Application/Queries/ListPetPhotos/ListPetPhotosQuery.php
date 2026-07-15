<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Queries\ListPetPhotos;

final class ListPetPhotosQuery
{
    public function __construct(public readonly string $petId) {}
}
