<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Réservations</h4></x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('share_text'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span id="shareText">{{ session('share_text') }}</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="navigator.share ? navigator.share({text: document.getElementById('shareText').innerText}) : alert('Partage non supporté')">Partager</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('shareText').innerText)">Copier</button>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="GET">
                <div class="col-md-2"><input type="date" class="form-control" name="from_date" value="{{ request('from_date', now()->toDateString()) }}"></div>
                <div class="col-md-2"><input type="date" class="form-control" name="to_date" value="{{ request('to_date', now()->toDateString()) }}"></div>
                <div class="col-md-2"><input type="text" class="form-control" name="room_number" placeholder="N° chambre" value="{{ request('room_number') }}"></div>
                <div class="col-md-2"><input type="text" class="form-control" name="client_name" placeholder="Client" value="{{ request('client_name') }}"></div>
                <div class="col-md-2">
                    <select name="payment_status" class="form-select">
                        <option value="">Paiement</option>
                        <option value="paid" @selected(request('payment_status')==='paid')>Payées</option>
                        <option value="unpaid" @selected(request('payment_status')==='unpaid')>Non payées</option>
                        <option value="partial" @selected(request('payment_status')==='partial')>Partielles</option>
                    </select>
                </div>
                <div class="col-md-1"><input type="number" min="1" name="nights" class="form-control" placeholder="Nuitées" value="{{ request('nights') }}"></div>
                <div class="col-md-1 d-flex gap-2">
                    <button class="btn btn-primary w-100">Filtrer</button>
                    <a class="btn btn-outline-dark" href="{{ route('reports.reservations.pdf', request()->query()) }}">PDF</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>Créer une réservation (modal)</div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createReservationModal">Nouvelle réservation</button>
        </div>
    </div>

    <div class="modal fade" id="createReservationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('reservations.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Nouvelle réservation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Client</option>
                                @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Chambre disponible</label>
                            <select class="form-select" name="room_id" required>
                                <option value="">Chambre disponible</option>
                                @foreach($availableRooms as $room)<option value="{{ $room->id }}">{{ $room->number }}</option>@endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">Date d’arrivée</label><input type="date" class="form-control" name="checkin_date" value="{{ old('checkin_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required></div>
                            <div class="col-md-6"><label class="form-label">Date prévue</label><input type="date" class="form-control" name="expected_checkout_date" value="{{ old('expected_checkout_date') }}" min="{{ now()->toDateString() }}"></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-success">Créer</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive card">
        <table class="table table-striped mb-0">
            <thead><tr><th>Chambre</th><th>Client</th><th>Date d’arrivée</th><th>Départ prévu</th><th>Départ réel</th><th>Nuitées</th><th>Total</th><th>Payé</th><th>Reste</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($reservations as $reservation)
                @php
                    $nights = $reservation->computeNights(now(), $hotel->checkout_time);
                    $paid = $reservation->payments->sum('amount');
                    $remaining = max(0, $reservation->total_amount - $paid);
                @endphp
                <tr>
                    <td>{{ $reservation->room->number }}</td>
                    <td>{{ $reservation->client->name }}</td>
                    <td>{{ $reservation->checkin_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->expected_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->actual_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $nights }}</td>
                    <td>{{ number_format($reservation->total_amount,2) }}</td>
                    <td>{{ number_format($paid,2) }}</td>
                    <td>{{ number_format($remaining,2) }}</td>
                    <td>{{ ['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$reservation->status] ?? $reservation->status }} / {{ ['unpaid' => 'non payé', 'partial' => 'partiel', 'paid' => 'payé'][$reservation->payment_status] ?? $reservation->payment_status }}</td>
                    <td class="d-flex gap-1">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('reservations.show',$reservation) }}">Voir</a>
                        <form method="POST" action="{{ route('reservations.update',$reservation) }}">@csrf @method('PUT')<input type="hidden" name="action" value="checkin"><button class="btn btn-sm btn-outline-success">Check-in</button></form>
                        <form method="POST" action="{{ route('reservations.update',$reservation) }}">@csrf @method('PUT')<input type="hidden" name="action" value="checkout"><button class="btn btn-sm btn-outline-danger">Check-out</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $reservations->links() }}</div>
</x-app-layout>
