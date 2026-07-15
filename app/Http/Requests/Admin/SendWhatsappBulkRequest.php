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

    protected function prepareForValidation(): void
    {
        $intent = (string) $this->input('intent', '');

        if ($intent !== '' && str_contains($intent, ':')) {
            [$phase, $action] = explode(':', $intent, 2);

            $this->merge([
                'phase' => $phase,
                'action' => $action,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'intent' => [
                'required',
                'string',
                Rule::in([
                    'reminder:all_pending',
                    'reminder:all_failed',
                    'reminder:selected',
                    'reminder:all_approved',
                    'access_card:all_ready',
                    'access_card:all_pending',
                    'access_card:all_failed',
                    'access_card:all_approved',
                    'access_card:selected',
                    'thank_you:all_pending',
                    'thank_you:all_failed',
                    'thank_you:all_approved',
                    'thank_you:selected',
                ]),
            ],
            'phase' => ['required', Rule::in(['reminder', 'access_card', 'thank_you'])],
            'action' => ['required', Rule::in(['selected', 'all_pending', 'all_failed', 'all_ready', 'all_approved'])],
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
            'intent.required' => 'Choose a WhatsApp send action.',
            'phase.required' => 'Choose reminder, access card, or thank you.',
            'action.required' => 'Choose a send action.',
        ];
    }
}
