<?php

declare(strict_types=1);

namespace App\Modules\Profile\Presentation\Http\Resources;

use App\Modules\Profile\Domain\Entities\PetPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetPhoto
 */
final class PetPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PetPhoto $photo */
        $photo = $this->resource;

        return [
            'id' => $photo->id()->toString(),
            'url' => $photo->url(),
            'is_primary' => $photo->isPrimary(),
            'created_at' => $photo->createdAt()->format(DATE_ATOM),
        ];
    }
}
