<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Requests;

use App\Modules\Shop\Domain\Enums\DeliveryMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CheckoutRequest extends FormRequest
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
            'delivery_method' => ['required', Rule::enum(DeliveryMethod::class)],
            'delivery_address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
