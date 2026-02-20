<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotelRequest;
use App\Models\Hotel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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
        $data['note'] = $request->input('note');

        if ($request->hasFile('image')) {
            if ($hotel?->image) {
                $previousPath = storage_path('app/public/' . $hotel->image);
                if (is_file($previousPath)) {
                    @unlink($previousPath);
                }
            }

            $data['image'] = $this->storeLogoFile($request->file('image'));
        }

        if ($hotel) {
            $hotel->update($data);
        } else {
            $hotel = Hotel::create(array_merge($data, [
                'owner_id' => $user->id,
            ]));
        }

        $user->update(['hotel_id' => $hotel->id]);

        return back()->with('success', 'Hôtel enregistré avec succès.');
    }

    private function storeLogoFile(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $directory = storage_path('app/public/hotels/logos');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = (string) Str::uuid() . '.' . $extension;

        $file->move($directory, $filename);

        return 'hotels/logos/' . $filename;
    }
}
