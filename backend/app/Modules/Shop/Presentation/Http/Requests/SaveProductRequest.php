<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveProductRequest extends FormRequest
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
            'category_id' => ['required', 'string', 'exists:shop_categories,id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_amount' => ['required', 'integer', 'min:1'],
            'stock' => ['required', 'integer', 'min:0'],
            'photo_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
