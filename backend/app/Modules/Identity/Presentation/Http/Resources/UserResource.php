<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Resources;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'email' => $this->email,
            'account_type' => $this->account_type,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
