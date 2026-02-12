<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminHotelController extends Controller
{
    public function index()
    {
        $hotels = Hotel::with('owner')->latest()->paginate(15);
        $users = User::where('role', '!=', 'super_admin')->orderBy('name')->get();
        $owners = User::where('role', 'owner')->orderBy('name')->get();

        return view('superadmin.hotels.index', compact('hotels', 'users', 'owners'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'checkout_time' => ['required', 'date_format:H:i'],
            'owner_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'owner'))],
        ]);

        DB::transaction(function () use ($data) {
            $hotel = Hotel::create([
                'owner_id' => $data['owner_id'],
                'name' => $data['hotel_name'],
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'phone' => $data['phone'] ?? null,
                'checkout_time' => $data['checkout_time'],
            ]);

            User::where('id', $data['owner_id'])->update(['hotel_id' => $hotel->id]);
        });

        return back()->with('success', 'Hôtel créé et propriétaire associé avec succès.');
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['owner', 'manager'])],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'hotel_id' => null,
        ]);

        return back()->with('success', 'Utilisateur créé avec succès.');
    }

    public function linkUser(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', '!=', 'super_admin'))],
            'hotel_id' => ['required', 'exists:hotels,id'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $hotel = Hotel::findOrFail($data['hotel_id']);

        DB::transaction(function () use ($user, $hotel) {
            $user->update(['hotel_id' => $hotel->id]);

            if ($user->role === 'owner') {
                $hotel->update(['owner_id' => $user->id]);
            }
        });

        return back()->with('success', 'Utilisateur lié à l’hôtel avec succès.');
    }
}
