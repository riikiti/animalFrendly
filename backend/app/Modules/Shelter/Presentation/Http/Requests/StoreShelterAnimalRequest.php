<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreShelterAnimalRequest extends FormRequest
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
            'species_id' => ['required', 'integer'],
            'breed_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'sex' => ['required', 'string', 'in:male,female'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_vaccinated' => ['nullable', 'boolean'],
        ];
    }
}
