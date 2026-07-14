<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Resources;

use App\Modules\Shelter\Domain\Entities\Shelter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shelter
 */
final class ShelterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Shelter $shelter */
        $shelter = $this->resource;

        return [
            'id' => $shelter->id()->toString(),
            'owner_user_id' => $shelter->ownerUserId()->toString(),
            'legal_name' => $shelter->legalName(),
            'inn' => $shelter->inn(),
            'description' => $shelter->description(),
            'verification_status' => $shelter->verificationStatus()->value,
            'verified_at' => $shelter->verifiedAt()?->format(DATE_ATOM),
        ];
    }
}
