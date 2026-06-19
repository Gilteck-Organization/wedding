<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRsvpsBulkRequest extends FormRequest
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
            'rsvp_ids' => ['required', 'array', 'min:1'],
            'rsvp_ids.*' => ['integer', 'exists:rsvps,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rsvp_ids.required' => 'Select at least one RSVP to delete.',
            'rsvp_ids.min' => 'Select at least one RSVP to delete.',
        ];
    }
}
