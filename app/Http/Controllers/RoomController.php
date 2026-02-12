<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Models\Apartment;
use App\Models\Room;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index()
    {
        $hotel = auth()->user()->currentHotel();
        if (! $hotel) {
            return redirect()->route('owner.hotels.index')
                ->with('success', 'Créez d\'abord votre hôtel pour gérer les chambres.');
        }

        $apartments = Apartment::where('hotel_id', $hotel->id)->orderBy('name')->get();
        $rooms = Room::whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->with('apartment')
            ->orderBy('order_index')
            ->get();

        return view('owner.rooms.index', compact('rooms', 'apartments'));
    }

    public function store(StoreRoomRequest $request)
    {
        $hotel = $request->user()->currentHotel();
        if (! $hotel) {
            return redirect()->route('owner.hotels.index')
                ->with('success', 'Créez d\'abord votre hôtel pour ajouter des chambres.');
        }

        $apartmentBelongsToHotel = Apartment::where('id', $request->integer('apartment_id'))
            ->where('hotel_id', $hotel->id)
            ->exists();

        if (! $apartmentBelongsToHotel) {
            throw ValidationException::withMessages([
                'apartment_id' => 'L\'appartement sélectionné n\'appartient pas à votre hôtel.',
            ]);
        }

        $roomCount = Room::whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))->count();
        $defaultPositionX = 20 + (($roomCount % 5) * 150);
        $defaultPositionY = 20 + (int) floor($roomCount / 5) * 110;

        $nextOrderIndex = (int) Room::whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->max('order_index') + 1;

        $dimensionWidth = (int) $request->input('dimension_width', 120);
        $dimensionHeight = (int) $request->input('dimension_height', 80);
        $dimension = $dimensionWidth.'x'.$dimensionHeight;

        Room::create([
            ...$request->validated(),
            'dimension' => $dimension,
            'position_x' => $defaultPositionX,
            'position_y' => $defaultPositionY,
            'order_index' => $nextOrderIndex,
            'status' => $request->input('status', 'available'),
        ]);

        return back()->with('success', 'Chambre ajoutée avec succès.');
    }

    public function update(StoreRoomRequest $request, Room $room)
    {
        $hotel = $request->user()->currentHotel();
        if (! $hotel) {
            return redirect()->route('owner.hotels.index')
                ->with('success', 'Créez d\'abord votre hôtel pour modifier les chambres.');
        }

        $roomBelongsToHotel = Room::where('id', $room->id)
            ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->exists();

        if (! $roomBelongsToHotel) {
            abort(403);
        }

        $apartmentBelongsToHotel = Apartment::where('id', $request->integer('apartment_id'))
            ->where('hotel_id', $hotel->id)
            ->exists();

        if (! $apartmentBelongsToHotel) {
            throw ValidationException::withMessages([
                'apartment_id' => 'L\'appartement sélectionné n\'appartient pas à votre hôtel.',
            ]);
        }

        $existingWidth = 120;
        $existingHeight = 80;
        if ($room->dimension && preg_match('/^(\d+)x(\d+)$/', $room->dimension, $matches)) {
            $existingWidth = (int) $matches[1];
            $existingHeight = (int) $matches[2];
        }

        $dimensionWidth = (int) $request->input('dimension_width', $existingWidth);
        $dimensionHeight = (int) $request->input('dimension_height', $existingHeight);
        $dimension = $dimensionWidth.'x'.$dimensionHeight;

        $room->update([
            ...$request->validated(),
            'dimension' => $dimension,
            'status' => $request->input('status', $room->status),
        ]);

        return back()->with('success', 'Chambre modifiée avec succès.');
    }
}
