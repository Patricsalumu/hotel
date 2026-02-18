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
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $hotelId = $user?->currentHotel()?->id;

        return [
            'client_id' => ['required', Rule::exists('clients', 'id')->where(fn ($query) => $query->where('hotel_id', $hotelId))],
            'room_id' => ['required', 'exists:rooms,id'],
            // checkin may be in the past or future; logic in controller will decide
            'checkin_date' => ['required', 'date'],
            'expected_checkout_date' => ['nullable', 'date', 'after_or_equal:checkin_date'],
            'status' => ['nullable', Rule::in(['reserved', 'checked_in'])],
        ];
    }
}
