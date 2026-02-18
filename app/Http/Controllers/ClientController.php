<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $hotel = $user->currentHotel();
        $clients = Client::with('reservations.room')
            ->where('hotel_id', $hotel->id)
            ->latest()
            ->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function store(StoreClientRequest $request)
    {
        $this->authorize('create', Client::class);
        $hotel = $request->user()->currentHotel();
        Client::create(array_merge($request->validated(), [
            'hotel_id' => $hotel->id,
        ]));

        return back()->with('success', 'Client ajouté avec succès.');
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $hotel = $user->currentHotel();
        if ($client->hotel_id !== $hotel->id) {
            abort(403);
        }

        $client->load(['reservations' => fn ($query) => $query
            ->whereHas('room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->with('room.payments')
            ->latest()]);

        return view('clients.show', compact('client'));
    }
}
