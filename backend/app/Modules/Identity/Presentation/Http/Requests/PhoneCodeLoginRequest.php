<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PhoneCodeLoginRequest extends FormRequest
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
            'phone' => ['required', 'string'],
            'code' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
