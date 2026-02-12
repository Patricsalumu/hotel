<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomLayoutRequest extends FormRequest
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
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.id' => ['required', 'exists:rooms,id'],
            'rooms.*.position_x' => ['required', 'integer'],
            'rooms.*.position_y' => ['required', 'integer'],
            'rooms.*.order_index' => ['required', 'integer', 'min:0'],
            'rooms.*.dimension' => ['required', 'regex:/^\d+x\d+$/'],
        ];
    }
}
