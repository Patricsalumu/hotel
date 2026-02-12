<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['owner', 'manager'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'checkin_date' => ['required', 'date', 'after_or_equal:today'],
            'expected_checkout_date' => ['nullable', 'date', 'after_or_equal:today', 'after_or_equal:checkin_date'],
            'status' => ['nullable', Rule::in(['reserved', 'checked_in'])],
        ];
    }
}
