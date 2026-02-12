<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApartmentRequest;
use App\Models\Apartment;

class ApartmentController extends Controller
{
    public function index()
    {
        $hotel = auth()->user()->currentHotel();
        if (! $hotel) {
            return redirect()->route('owner.hotels.index')
                ->with('success', 'Créez d\'abord votre hôtel pour gérer les appartements.');
        }

        $apartments = Apartment::where('hotel_id', $hotel->id)->withCount('rooms')->get();
        return view('owner.apartments.index', compact('apartments'));
    }

    public function store(StoreApartmentRequest $request)
    {
        $hotel = $request->user()->currentHotel();
        if (! $hotel) {
            return redirect()->route('owner.hotels.index')
                ->with('success', 'Créez d\'abord votre hôtel pour ajouter des appartements.');
        }

        Apartment::create([
            'hotel_id' => $hotel->id,
            ...$request->validated(),
        ]);

        return back()->with('success', 'Appartement ajouté avec succès.');
    }
}
