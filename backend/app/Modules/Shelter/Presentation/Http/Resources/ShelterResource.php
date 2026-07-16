<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Resources;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;
use App\Modules\Shelter\Domain\Entities\Shelter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Принимает либо Shelter напрямую (create/me/verify/pending-verification — без контактов
 * владельца), либо array{shelter: Shelter, owner: ?EloquentUser} — так отдаёт show() уже
 * после проверки видимости, тот же приём, что ConversationResource/OrderResource.
 */
final class ShelterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shelter = $this->resource instanceof Shelter ? $this->resource : $this->resource['shelter'];
        $owner = is_array($this->resource) ? $this->resource['owner'] : null;

        return [
            'id' => $shelter->id()->toString(),
            'owner_user_id' => $shelter->ownerUserId()->toString(),
            'legal_name' => $shelter->legalName(),
            'inn' => $shelter->inn(),
            'description' => $shelter->description(),
            'verification_status' => $shelter->verificationStatus()->value,
            'verified_at' => $shelter->verifiedAt()?->format(DATE_ATOM),
            'address' => $shelter->address(),
            'city' => $shelter->city(),
            'phone' => $shelter->phone(),
            'email' => $shelter->email(),
            'photo_url' => $shelter->photoUrl(),
            'owner' => $owner instanceof EloquentUser ? [
                'name' => $owner->name,
                'phone' => $owner->phone,
                'avatar_url' => $owner->avatar_url,
            ] : null,
        ];
    }
}
