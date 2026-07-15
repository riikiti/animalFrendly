<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitReportRequest extends FormRequest
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
            'target_type' => ['required', 'string', 'in:pet,listing,user,message'],
            'target_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'in:spam,inappropriate,scam,other'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
