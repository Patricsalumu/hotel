<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRoomLayoutRequest;
use App\Models\Room;

class RoomLayoutController extends Controller
{
    public function update(UpdateRoomLayoutRequest $request)
    {
        $hotelId = $request->user()->currentHotel()->id;

        foreach ($request->validated('rooms') as $roomData) {
            Room::where('id', $roomData['id'])
                ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotelId))
                ->update([
                    'position_x' => $roomData['position_x'],
                    'position_y' => $roomData['position_y'],
                    'order_index' => $roomData['order_index'],
                    'dimension' => $roomData['dimension'],
                ]);
        }

        return response()->json(['message' => 'Layout des chambres mis à jour.']);
    }
}
