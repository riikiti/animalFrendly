<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitReviewRequest extends FormRequest
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
            'order_id' => ['nullable', 'string', 'required_without:adoption_request_id'],
            'adoption_request_id' => ['nullable', 'string', 'required_without:order_id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
