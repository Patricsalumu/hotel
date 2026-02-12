<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotelRequest;
use App\Models\Hotel;

class HotelController extends Controller
{
    public function index()
    {
        $hotel = auth()->user()->ownedHotel;
        return view('owner.hotels.index', compact('hotel'));
    }

    public function store(StoreHotelRequest $request)
    {
        $user = $request->user();
        $hotel = Hotel::updateOrCreate(
            ['owner_id' => $user->id],
            $request->validated()
        );

        $user->update(['hotel_id' => $hotel->id]);

        return back()->with('success', 'Hôtel enregistré avec succès.');
    }
}
