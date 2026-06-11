<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendWhatsappBulkRequest extends FormRequest
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
            'action' => ['required', Rule::in(['selected', 'all_pending', 'all_failed', 'all_approved'])],
            'guest_ids' => ['nullable', 'array'],
            'guest_ids.*' => ['integer', 'exists:guests,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Choose a send action.',
            'action.in' => 'Invalid send action.',
        ];
    }
}
