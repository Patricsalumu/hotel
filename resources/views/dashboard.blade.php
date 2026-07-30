<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Tableau de bord</h4>
                <div class="small text-white-50">Vue opérationnelle de l'hôtel en temps réel</div>
            </div>
            @if($hotel)
                <span class="badge rounded-pill" style="background: rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.25);">{{ $hotel->name }}</span>
            @endif
        </div>
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
        @php
            $currency = $hotel->currency ?? 'FC';
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="gh-kpi h-100">
                    <div class="gh-kpi-label">Chambres occupées</div>
                    <div class="gh-kpi-value text-danger">{{ $occupied }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="gh-kpi h-100">
                    <div class="gh-kpi-label">Chambres réservées</div>
                    <div class="gh-kpi-value text-warning">{{ $reserved }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="gh-kpi h-100">
                    <div class="gh-kpi-label">Chambres libres</div>
                    <div class="gh-kpi-value text-success">{{ $available }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="gh-kpi h-100">
                    <div class="gh-kpi-label">Encaissement du jour</div>
                    <div class="gh-kpi-value">{{ \App\Support\Money::format($todayIncome, $currency) }}</div>
                    <div class="small text-muted">Dépenses: {{ \App\Support\Money::format($todayExpenses, $currency) }}</div>
                </div>
            </div>
        </div>
        <div class="gh-card card">
            <div class="card-header">Chambres</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($rooms as $room)
                        @php $latest = $room->reservations->first(); @endphp
                        <div class="col-md-3">
                            <div class="gh-room-card p-3 h-100">
                                <div class="d-flex justify-content-between">
                                    <strong>#{{ $room->number }}</strong>
                                    <span class="badge text-bg-{{ $room->status === 'occupied' ? 'danger' : ($room->status === 'reserved' ? 'warning' : 'success') }}">{{ ['occupied' => 'occupée', 'reserved' => 'réservée', 'available' => 'libre'][$room->status] ?? $room->status }}</span>
                                </div>
                                @if(in_array($room->status, ['occupied', 'reserved'], true) && $latest)
                                    @php
                                        $nights = $latest->computeNights(now(), $hotel->checkout_time);
                                        $checkin = $latest->checkin_date?->format('Y-m-d') ?? '';
                                        $expected = $latest->expected_checkout_date?->format('Y-m-d');
                                    @endphp
                                    <div class="small mt-2">{{ $checkin }}@if($expected) - {{ $expected }}@endif</div>
                                    <div class="small text-muted">{{ $latest->client->name ?? '-' }} • {{ $nights }} nuitée(s)</div>
                                    <div class="d-flex gap-2 flex-wrap mt-2">
                                        <a href="{{ route('reservations.show', $latest) }}" class="btn btn-sm btn-outline-primary">Voir réservation</a>
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changeRoomModal" data-reservation-id="{{ $latest->id }}" data-room-id="{{ $room->id }}" data-room-number="{{ $room->number }}" data-apartment-id="{{ $room->apartment_id }}" data-apartment-name="{{ $room->apartment->name }}">Changer</button>
                                    </div>
                                @elseif($room->status === 'available')
                                    <button class="btn btn-sm gh-btn-primary btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#reservationModal" data-apartment-id="{{ $room->apartment_id }}" data-room-id="{{ $room->id }}" data-room-number="{{ $room->number }}" data-apartment-name="{{ $room->apartment->name }}">Check-in</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('reservations.update', ['reservation' => 0]) }}" id="dashboardCheckinForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="checkin">
                        <div class="modal-header">
                            <h5 class="modal-title">Check-in <span class="badge text-bg-info ms-1">Raccourci dashboard</span> <span id="apartmentLabel" class="text-muted"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="apartment_id" id="modalApartmentId">
                            <input type="hidden" id="modalRoomId" name="room_id">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Réservation</label>
                                    <select class="form-select" name="reservation_id" id="dashboardReservationSelect" required>
                                        <option value="">Sélectionner une réservation</option>
                                    </select>
                                    <div class="alert alert-warning d-none mt-2" id="dashboardNoReservationsAlert">Aucune réservation disponible pour cet appartement.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date d'entrée</label>
                                    <input type="date" class="form-control" id="dashboardCheckinDate" name="checkin_date" required>
                                </div>
                            </div>
                            <div class="mt-3 border rounded p-2 bg-light">
                                <div class="d-flex justify-content-between"><span>Réservation sélectionnée</span><strong id="dashboardSelectedReservationInfo">Aucune</strong></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button id="dashboardConfirmCheckinBtn" class="btn btn-primary">Confirmer check-in</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="changeRoomModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="" id="dashboardChangeRoomForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="change_room">
                        <input type="hidden" id="changeRoomToRoomId" name="room_id">
                        <div class="modal-header">
                            <h5 class="modal-title">Changer de chambre</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="small text-muted">Chambre actuelle</div>
                                    <div class="fw-semibold" id="changeRoomCurrentLabel">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="small text-muted">Appartement</div>
                                    <div class="fw-semibold" id="changeRoomApartmentLabel">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="small text-muted">Chambres disponibles</div>
                                    <div class="list-group" id="changeRoomAvailableList"></div>
                                    <div class="alert alert-warning d-none mt-2" id="changeRoomNoRoomAlert">Aucune chambre disponible pour cet appartement.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary" id="changeRoomConfirmBtn" disabled>Confirmer le changement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="dashboardCreateClientQuickModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="dashboardCreateClientQuickForm">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Nouveau client</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="row g-2">
                                <div class="col-12"><label class="form-label">Nom</label><input type="text" class="form-control" name="name" required></div>
                                <div class="col-md-6"><label class="form-label">Téléphone</label><input type="text" class="form-control" name="phone"></div>
                                <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                                <div class="col-md-6"><label class="form-label">Nationalité</label><input type="text" class="form-control" name="nationality"></div>
                                <div class="col-md-6"><label class="form-label">N° document</label><input type="text" class="form-control" name="document_number"></div>
                            </div>
                            <div class="small mt-2" id="dashboardClientCreateFeedback"></div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" type="submit">Créer et sélectionner</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div id="dashboardData"
            data-is-owner="{{ $isOwner ? '1' : '0' }}"
            data-reservation-base-url="{{ url('reservations') }}"
            data-currency="{{ $currency }}"
            data-clients-search-route="{{ route('clients.search') }}"
            data-clients-quick-store-route="{{ route('clients.quick-store') }}"
        ></div>
        <script id="dashboardReservationsData" type="application/json">{!! json_encode($dashboardReservations, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
        <script id="dashboardAvailableRoomsData" type="application/json">{!! json_encode($availableRooms, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
        <script src="{{ asset('js/dashboard.js') }}"></script>
    @endif
</x-app-layout>
