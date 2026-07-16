<?php

declare(strict_types=1);

namespace App\Modules\Profile\Presentation\Http\Requests;

use App\Modules\Profile\Domain\Enums\PetSocialTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePetRequest extends FormRequest
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
            // 'for_sale'/'shelter' назначаются модулями Marketplace/Shelter, не отсюда —
            // см. docs/plan/07-flow-matching-shelter.md.
            'purpose' => ['required', 'string', 'in:social,breeding'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_vaccinated' => ['nullable', 'boolean'],
            'social_tags' => ['sometimes', 'array'],
            'social_tags.*' => ['string', Rule::in(array_column(PetSocialTag::cases(), 'value'))],
        ];
    }
}
