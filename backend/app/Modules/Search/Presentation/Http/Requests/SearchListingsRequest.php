<?php

declare(strict_types=1);

namespace App\Modules\Search\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchListingsRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:200'],
            'species_id' => ['nullable', 'integer'],
            'breed_id' => ['nullable', 'integer'],
            'city' => ['nullable', 'string', 'max:120'],
            'min_price_amount' => ['nullable', 'integer', 'min:0'],
            'max_price_amount' => ['nullable', 'integer', 'min:0'],
            'radius_km' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
