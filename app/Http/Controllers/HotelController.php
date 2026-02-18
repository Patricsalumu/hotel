<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotelRequest;
use App\Models\Hotel;
use Illuminate\Support\Facades\Storage;

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
        $hotel = $user->ownedHotel;
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($hotel?->image) {
                Storage::disk('public')->delete($hotel->image);
            }

            $data['image'] = $request->file('image')->store('hotels/logos', 'public');
        }

        $hotel = Hotel::updateOrCreate(
            ['owner_id' => $user->id],
            $data
        );

        $user->update(['hotel_id' => $hotel->id]);

        return back()->with('success', 'Hôtel enregistré avec succès.');
    }
}
