<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveRsvpsBulkRequest extends FormRequest
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
            'action' => ['required', Rule::in(['selected', 'all_pending'])],
            'rsvp_ids' => ['nullable', 'array'],
            'rsvp_ids.*' => ['integer', 'exists:rsvps,id'],
        ];
    }
}
