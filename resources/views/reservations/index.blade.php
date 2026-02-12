<x-app-layout>
    <style>
        .rv-toolbar .form-label {
            font-size: .74rem;
            color: #64748b;
            margin-bottom: .2rem;
            text-transform: uppercase;
            letter-spacing: .02em;
            font-weight: 600;
        }

        .rv-kpi {
            border: 1px solid #e7ebf1;
            border-radius: 12px;
            padding: .8rem 1rem;
            background: #fff;
        }

        .rv-kpi-label {
            color: #64748b;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .rv-kpi-value {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
            color: #111827;
        }

        .rv-table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .rv-client {
            min-width: 170px;
            max-width: 220px;
            white-space: normal !important;
        }

        .rv-status {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            min-width: 150px;
        }

        .rv-actions {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            min-width: 210px;
        }

        @media (max-width: 575.98px) {
            .rv-kpi-value {
                font-size: 1.08rem;
            }

            .rv-actions {
                min-width: 180px;
            }
        }
    </style>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Réservations</h4>
                <div class="small text-white-50">Suivi complet des séjours, statuts et règlements</div>
            </div>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createReservationModal">Nouvelle réservation</button>
        </div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('share_text'))
        <div class="alert alert-info d-flex justify-content-between align-items-center gap-2 gh-mobile-stack">
            <span id="shareText">{{ session('share_text') }}</span>
            <div class="d-flex gap-2 gh-mobile-stack">
                <button class="btn btn-sm btn-outline-primary" onclick="navigator.share ? navigator.share({text: document.getElementById('shareText').innerText}) : alert('Partage non supporté')">Partager</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('shareText').innerText)">Copier</button>
            </div>
        </div>
    @endif

    @php
        $pageTotalAmount = $reservations->sum('total_amount');
        $pagePaidAmount = $reservations->sum(fn ($reservation) => $reservation->payments->sum('amount'));
        $pageRemainingAmount = max(0, $pageTotalAmount - $pagePaidAmount);
    @endphp

    <div class="row g-2 mb-3">
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Réservations (page)</div><div class="rv-kpi-value">{{ $reservations->count() }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Montant total</div><div class="rv-kpi-value">{{ number_format($pageTotalAmount, 2) }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Total payé</div><div class="rv-kpi-value text-success">{{ number_format($pagePaidAmount, 2) }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Reste à payer</div><div class="rv-kpi-value text-danger">{{ number_format($pageRemainingAmount, 2) }}</div></div></div>
    </div>

    <div class="gh-card card mb-3">
        <div class="card-body">
            <form class="row g-2 rv-toolbar" method="GET">
                <div class="col-md-2"><label class="form-label">Du</label><input type="date" class="form-control" name="from_date" value="{{ request('from_date', now()->toDateString()) }}"></div>
                <div class="col-md-2"><label class="form-label">Au</label><input type="date" class="form-control" name="to_date" value="{{ request('to_date', now()->toDateString()) }}"></div>
                <div class="col-md-2"><label class="form-label">Chambre</label><input type="text" class="form-control" name="room_number" placeholder="N° chambre" value="{{ request('room_number') }}"></div>
                <div class="col-md-2"><label class="form-label">Client</label><input type="text" class="form-control" name="client_name" placeholder="Nom client" value="{{ request('client_name') }}"></div>
                <div class="col-md-2">
                    <label class="form-label">Paiement</label>
                    <select name="payment_status" class="form-select">
                        <option value="">Paiement</option>
                        <option value="paid" @selected(request('payment_status')==='paid')>Payées</option>
                        <option value="unpaid" @selected(request('payment_status')==='unpaid')>Non payées</option>
                        <option value="partial" @selected(request('payment_status')==='partial')>Partielles</option>
                    </select>
                </div>
                <div class="col-md-1"><label class="form-label">Nuits</label><input type="number" min="1" name="nights" class="form-control" placeholder="Nuitées" value="{{ request('nights') }}"></div>
                <div class="col-md-3 d-flex gap-2 gh-mobile-stack">
                    <button class="btn gh-btn-primary btn-primary w-100">Filtrer</button>
                    <a class="btn btn-outline-dark" href="{{ route('reports.reservations.pdf', request()->query()) }}">PDF</a>
                </div>
            </form>
        </div>
    </div>

    <div class="gh-card card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center gap-2 gh-mobile-stack">
            <div class="fw-semibold">Créer une réservation</div>
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
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn gh-btn-primary btn-primary">Créer</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="gh-card card table-responsive">
        <table class="table table-hover align-middle mb-0 rv-table">
            <thead class="table-light"><tr><th>Chambre</th><th>Client</th><th>Date d’arrivée</th><th>Départ prévu</th><th>Départ réel</th><th>Nuitées</th><th>Total</th><th>Payé</th><th>Reste</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($reservations as $reservation)
                @php
                    $nights = $reservation->computeNights(now(), $hotel->checkout_time);
                    $paid = $reservation->payments->sum('amount');
                    $remaining = max(0, $reservation->total_amount - $paid);
                @endphp
                <tr>
                    <td>{{ $reservation->room->number }}</td>
                    <td class="rv-client">{{ $reservation->client->name }}</td>
                    <td>{{ $reservation->checkin_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->expected_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->actual_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $nights }}</td>
                    <td><span class="fw-semibold">{{ number_format($reservation->total_amount,2) }}</span></td>
                    <td><span class="text-success fw-semibold">{{ number_format($paid,2) }}</span></td>
                    <td><span class="text-danger fw-semibold">{{ number_format($remaining,2) }}</span></td>
                    <td class="rv-status">
                        <span class="badge text-bg-{{ $reservation->status === 'checked_out' ? 'secondary' : ($reservation->status === 'checked_in' ? 'warning' : 'info') }}">{{ ['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$reservation->status] ?? $reservation->status }}</span>
                        <span class="badge text-bg-{{ $reservation->payment_status === 'paid' ? 'success' : ($reservation->payment_status === 'partial' ? 'warning' : 'danger') }}">{{ ['unpaid' => 'non payé', 'partial' => 'partiel', 'paid' => 'payé'][$reservation->payment_status] ?? $reservation->payment_status }}</span>
                    </td>
                    <td class="rv-actions">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('reservations.show',$reservation) }}">Voir</a>
                        <form method="POST" action="{{ route('reservations.update',$reservation) }}">@csrf @method('PUT')<input type="hidden" name="action" value="checkin"><button class="btn btn-sm btn-outline-success">Check-in</button></form>
                        <form method="POST" action="{{ route('reservations.update',$reservation) }}">@csrf @method('PUT')<input type="hidden" name="action" value="checkout"><button class="btn btn-sm btn-outline-danger">Check-out</button></form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">
                        <div class="gh-empty my-2">Aucune réservation trouvée pour les filtres sélectionnés.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $reservations->links() }}</div>
</x-app-layout>
