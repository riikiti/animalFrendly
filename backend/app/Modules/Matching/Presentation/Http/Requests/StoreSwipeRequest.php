<?php

declare(strict_types=1);

namespace App\Modules\Matching\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSwipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_pet_id' => ['required', 'string'],
            'action' => ['required', 'string', 'in:like,dislike,super_like'],
        ];
    }
}
