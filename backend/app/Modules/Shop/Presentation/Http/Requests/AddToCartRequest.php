<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddToCartRequest extends FormRequest
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
            'product_id' => ['required', 'string'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
        ];
    }
}
