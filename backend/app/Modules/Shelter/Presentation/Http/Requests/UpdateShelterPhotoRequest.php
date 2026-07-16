<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateShelterPhotoRequest extends FormRequest
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
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ];
    }
}
