<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreShelterRequest extends FormRequest
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
            'legal_name' => ['required', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
