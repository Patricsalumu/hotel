<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'apartment_id' => ['required', 'exists:apartments,id'],
            'number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['simple', 'double', 'suite'])],
            'price_per_night' => ['required', 'numeric', 'min:0.01'],
            'dimension' => ['nullable', 'string', 'max:50'],
            'dimension_width' => ['nullable', 'integer', 'min:60', 'max:500'],
            'dimension_height' => ['nullable', 'integer', 'min:40', 'max:300'],
            'shape' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['available', 'reserved', 'occupied'])],
        ];
    }
}
