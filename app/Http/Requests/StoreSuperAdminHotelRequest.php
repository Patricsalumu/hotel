<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuperAdminHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'hotel_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'checkout_time' => ['required', 'date_format:H:i'],

            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hotel_name' => 'nom de l\'hôtel',
            'checkout_time' => 'heure limite de départ',
            'owner_name' => 'nom du propriétaire',
            'owner_email' => 'email du propriétaire',
            'owner_password' => 'mot de passe du propriétaire',
        ];
    }
}
