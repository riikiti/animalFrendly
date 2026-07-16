<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Resources;

use App\Modules\Marketplace\Domain\Entities\Breeder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BreederResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Breeder $breeder */
        $breeder = $this->resource;

        return [
            'id' => $breeder->id()->toString(),
            'owner_user_id' => $breeder->ownerUserId()->toString(),
            'verification_status' => $breeder->verificationStatus()->value,
            'verified_at' => $breeder->verifiedAt()?->format(DATE_ATOM),
        ];
    }
}
