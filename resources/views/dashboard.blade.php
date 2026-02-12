<x-app-layout>
    <x-slot name="header">
        <h4 class="mb-0">Tableau de bord</h4>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if(!$hotel)
        <div class="alert alert-warning">Aucun hôtel configuré pour ce compte.</div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card"><div class="card-body"><strong>Occupées</strong><div class="fs-4">{{ $occupied }}</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><strong>Réservées</strong><div class="fs-4">{{ $reserved }}</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><strong>Libres</strong><div class="fs-4">{{ $available }}</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><strong>Encaissement du jour</strong><div class="fs-5">{{ number_format($todayIncome,2) }}</div><small>Dépenses: {{ number_format($todayExpenses,2) }}</small></div></div></div>
        </div>

        <div class="card mb-4"><div class="card-body">
            @php
                $total = max(1, $occupied + $reserved + $available);
                $occupiedPct = round(($occupied / $total) * 100, 2);
                $reservedPct = round(($reserved / $total) * 100, 2);
                $availablePct = round(($available / $total) * 100, 2);
            @endphp
            <div class="mb-2 fw-semibold">Graphique chambres</div>
            <div class="progress" role="progressbar" aria-label="Chambres">
                <div class="progress-bar bg-danger room-progress" data-width="{{ $occupiedPct }}">Occupées</div>
                <div class="progress-bar bg-warning room-progress" data-width="{{ $reservedPct }}">Réservées</div>
                <div class="progress-bar bg-success room-progress" data-width="{{ $availablePct }}">Libres</div>
            </div>
        </div></div>

        <div class="card">
            <div class="card-header">Chambres</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($rooms as $room)
                        @php $latest = $room->reservations->first(); @endphp
                        <div class="col-md-3">
                            <div class="border rounded p-2 h-100">
                                <div class="d-flex justify-content-between">
                                    <strong>#{{ $room->number }}</strong>
                                    <span class="badge text-bg-{{ $room->status === 'occupied' ? 'danger' : ($room->status === 'reserved' ? 'warning' : 'success') }}">{{ ['occupied' => 'occupée', 'reserved' => 'réservée', 'available' => 'libre'][$room->status] ?? $room->status }}</span>
                                </div>
                                @if($room->status === 'occupied' && $latest)
                                    <div class="small mt-2">Client: {{ $latest->client->name ?? '-' }}</div>
                                    <a href="{{ route('reservations.show', $latest) }}" class="btn btn-sm btn-outline-primary mt-2">Voir réservation</a>
                                @elseif($room->status === 'available')
                                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#reservationModal" data-room-id="{{ $room->id }}" data-room-number="{{ $room->number }}">Créer réservation</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('reservations.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Nouvelle réservation <span id="roomLabel" class="text-muted"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="room_id" id="modalRoomId">
                            <div class="mb-2">
                                <label class="form-label">Client</label>
                                <select class="form-select" name="client_id" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6"><label class="form-label">Checkin</label><input type="date" class="form-control" name="checkin_date" value="{{ old('checkin_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required></div>
                                <div class="col-md-6"><label class="form-label">Checkout prévu</label><input type="date" class="form-control" name="expected_checkout_date" value="{{ old('expected_checkout_date') }}" min="{{ now()->toDateString() }}"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.querySelectorAll('.room-progress').forEach(el => {
                el.style.width = `${el.dataset.width}%`;
            });

            const reservationModal = document.getElementById('reservationModal');
            reservationModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                document.getElementById('modalRoomId').value = button.getAttribute('data-room-id');
                document.getElementById('roomLabel').textContent = `(#${button.getAttribute('data-room-number')})`;
            });
        </script>
    @endif
</x-app-layout>
