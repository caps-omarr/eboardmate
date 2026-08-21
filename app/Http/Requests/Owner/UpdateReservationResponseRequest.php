<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isReject = $this->routeIs('owner.reservations.reject');

        return [
            'owner_response' => [$isReject ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_response.required' => 'Please provide a reason for declining the reservation.',
        ];
    }
}
