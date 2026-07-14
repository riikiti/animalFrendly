<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResolveDisputeRequest extends FormRequest
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
            'resolution' => ['required', 'string', 'in:seller_wins,buyer_wins'],
        ];
    }
}
