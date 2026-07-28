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
        $currency = $hotel->currency ?? 'FC';

        return view('owner.apartments.index', compact('apartments', 'currency'));
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

    public function update(StoreApartmentRequest $request, Apartment $apartment)
    {
        $hotel = $request->user()->currentHotel();
        if (! $hotel || $apartment->hotel_id !== $hotel->id) {
            abort(403);
        }

        $apartment->update($request->validated());

        return back()->with('success', 'Appartement modifié avec succès.');
    }

    public function destroy(Apartment $apartment)
    {
        $hotel = auth()->user()->currentHotel();
        if (! $hotel || $apartment->hotel_id !== $hotel->id) {
            abort(403);
        }

        if ($apartment->rooms()->exists()) {
            return back()->with('error', 'Impossible de supprimer un appartement contenant des chambres.');
        }

        $apartment->delete();

        return back()->with('success', 'Appartement supprimé avec succès.');
    }
}
