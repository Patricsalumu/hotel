<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseAccountRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('expense_accounts', 'name')->where(fn ($query) => $query->where('hotel_id', $hotelId)),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
