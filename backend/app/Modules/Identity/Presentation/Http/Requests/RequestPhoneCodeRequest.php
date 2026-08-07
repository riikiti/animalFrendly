<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Requests;

use App\Modules\Identity\Domain\Enums\PhoneCodePurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RequestPhoneCodeRequest extends FormRequest
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
            'purpose' => ['required', Rule::enum(PhoneCodePurpose::class)],
        ];
    }
}
