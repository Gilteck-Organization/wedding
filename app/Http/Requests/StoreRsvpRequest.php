<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'attendance' => ['required', 'in:yes,no'],
            'guest_count' => ['required_if:attendance,yes', 'nullable', 'integer', 'in:1,2'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guest_count.required_if' => 'Please choose how many people are in your party.',
            'guest_count.in' => 'Each invitation includes you and at most one guest (2 people total).',
        ];
    }
}
