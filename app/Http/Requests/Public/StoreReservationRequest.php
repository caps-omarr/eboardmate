<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|outlook\.com)$/i',
            ],
            'phone' => ['required', 'string', 'max:30'],
            'preferred_move_in_date' => ['required', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:1000'],
            'accepted_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.regex' => 'Please use a valid email address from standard providers (Gmail, Yahoo, or Outlook).',
            'accepted_terms.accepted' => 'You must accept the terms and conditions to proceed with your reservation.',
        ];
    }
}
