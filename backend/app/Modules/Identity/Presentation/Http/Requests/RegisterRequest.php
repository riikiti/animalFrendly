<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
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
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            // 'admin'/'moderator' нельзя выбрать через публичную регистрацию.
            'account_type' => ['required', 'string', 'in:owner,breeder,shelter'],
            'personal_data_consent' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'personal_data_consent.accepted' => 'Необходимо дать согласие на обработку персональных данных.',
        ];
    }
}
