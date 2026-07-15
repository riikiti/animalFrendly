<?php

declare(strict_types=1);

namespace App\Modules\Notification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterDeviceTokenRequest extends FormRequest
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
            'platform' => ['required', 'string', 'in:android,ios'],
            'fcm_token' => ['required', 'string', 'max:255'],
        ];
    }
}
