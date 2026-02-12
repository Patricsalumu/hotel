<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('reservations.room')->latest()->paginate(15);
        return view('clients.index', compact('clients'));
    }

    public function store(StoreClientRequest $request)
    {
        $this->authorize('create', Client::class);
        Client::create($request->validated());
        return back()->with('success', 'Client ajouté avec succès.');
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);
        $client->load('reservations.room.payments');
        return view('clients.show', compact('client'));
    }
}
