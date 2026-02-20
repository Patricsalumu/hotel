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

        <div class="gh-card card mb-4"><div class="card-body">
            @php
                $total = max(1, $occupied + $reserved + $available);
                $occupiedPct = round(($occupied / $total) * 100, 2);
                $reservedPct = round(($reserved / $total) * 100, 2);
                $availablePct = round(($available / $total) * 100, 2);
            @endphp
            <div class="mb-2 fw-semibold">Occupation des chambres</div>
            <div class="progress gh-progress" role="progressbar" aria-label="Chambres">
                <div class="progress-bar bg-danger room-progress" data-width="{{ $occupiedPct }}">Occupées</div>
                <div class="progress-bar bg-warning room-progress" data-width="{{ $reservedPct }}">Réservées</div>
                <div class="progress-bar bg-success room-progress" data-width="{{ $availablePct }}">Libres</div>
            </div>
            <div class="row mt-2 small text-muted">
                <div class="col">Occupées: {{ $occupiedPct }}%</div>
                <div class="col">Réservées: {{ $reservedPct }}%</div>
                <div class="col">Libres: {{ $availablePct }}%</div>
            </div>
        </div></div>

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
                                <div class="small text-muted mt-1">{{ $room->apartment->name ?? '-' }} • {{ ucfirst($room->type) }}</div>
                                @if($room->status === 'occupied' && $latest)
                                    <div class="small mt-2">Client: {{ $latest->client->name ?? '-' }}</div>
                                    <a href="{{ route('reservations.show', $latest) }}" class="btn btn-sm btn-outline-primary mt-2">Voir réservation</a>
                                @elseif($room->status === 'available')
                                    <button class="btn btn-sm gh-btn-primary btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#reservationModal" data-room-id="{{ $room->id }}" data-room-number="{{ $room->number }}" data-room-price="{{ (float) $room->price_per_night }}">Créer réservation</button>
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
                        <input type="hidden" name="creation_source" value="dashboard_shortcut">
                        <div class="modal-header">
                            <h5 class="modal-title">Nouvelle réservation <span class="badge text-bg-info ms-1">Raccourci dashboard</span> <span id="roomLabel" class="text-muted"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="room_id" id="modalRoomId">
                            <input type="hidden" id="modalRoomPrice" value="0">
                            <div class="mb-2">
                                <label class="form-label">Rechercher client</label>
                                <input type="text" class="form-control" id="dashboardClientSearchInput" placeholder="Tapez une lettre...">
                                <div class="small text-muted mt-1" id="dashboardClientSearchFeedback"></div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Client</label>
                                <select class="form-select" name="client_id" id="dashboardClientSelect" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="dashboardOpenCreateClientBtn" data-bs-toggle="modal" data-bs-target="#dashboardCreateClientQuickModal">Nouveau client</button>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6"><label class="form-label">Checkin</label><input type="date" class="form-control" id="dashboardCheckinDate" name="checkin_date" value="{{ old('checkin_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required></div>
                                <div class="col-md-6"><label class="form-label">Checkout prévu</label><input type="date" class="form-control" id="dashboardCheckoutDate" name="expected_checkout_date" value="{{ old('expected_checkout_date') }}" min="{{ now()->toDateString() }}"></div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Réduction</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="dashboardDiscountAmount" name="discount_amount" value="{{ old('discount_amount', 0) }}" placeholder="0">
                            </div>
                            <div class="mt-3 border rounded p-2 bg-light">
                                <div class="d-flex justify-content-between"><span>Total à payer</span><strong id="dashboardGrossAmount">0 {{ $currency }}</strong></div>
                                <div class="d-flex justify-content-between"><span>Réduction</span><strong id="dashboardDiscountPreview">0 {{ $currency }}</strong></div>
                                <div class="d-flex justify-content-between"><span>Net à payer</span><strong id="dashboardNetAmount">0 {{ $currency }}</strong></div>
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

        <script>
            document.querySelectorAll('.room-progress').forEach(el => {
                el.style.width = `${el.dataset.width}%`;
            });

            const reservationModal = document.getElementById('reservationModal');
            reservationModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                document.getElementById('modalRoomId').value = button.getAttribute('data-room-id');
                document.getElementById('modalRoomPrice').value = button.getAttribute('data-room-price') || '0';
                document.getElementById('roomLabel').textContent = `(#${button.getAttribute('data-room-number')})`;
                computeDashboardAmounts();
            });

            const dashboardClientSearchInput = document.getElementById('dashboardClientSearchInput');
            const dashboardClientSelect = document.getElementById('dashboardClientSelect');
            const dashboardClientSearchFeedback = document.getElementById('dashboardClientSearchFeedback');
            const dashboardCreateClientModalEl = document.getElementById('dashboardCreateClientQuickModal');
            const dashboardClientCreateForm = document.getElementById('dashboardCreateClientQuickForm');
            const dashboardClientCreateFeedback = document.getElementById('dashboardClientCreateFeedback');
            const dashboardCheckinDate = document.getElementById('dashboardCheckinDate');
            const dashboardCheckoutDate = document.getElementById('dashboardCheckoutDate');
            const dashboardDiscountAmount = document.getElementById('dashboardDiscountAmount');
            const dashboardGrossAmount = document.getElementById('dashboardGrossAmount');
            const dashboardDiscountPreview = document.getElementById('dashboardDiscountPreview');
            const dashboardNetAmount = document.getElementById('dashboardNetAmount');

            const dashboardClients = [...(dashboardClientSelect?.options || [])]
                .filter((option) => option.value)
                .map((option) => ({ id: Number(option.value), name: option.textContent || '' }));

            const formatMoney = (value) => {
                const numeric = Number(value || 0);
                return `${numeric.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} {{ $currency }}`;
            };

            const computeDashboardAmounts = () => {
                const nightly = Number(document.getElementById('modalRoomPrice')?.value || 0);
                const checkinDate = dashboardCheckinDate?.value ? new Date(dashboardCheckinDate.value) : null;
                const checkoutDate = dashboardCheckoutDate?.value ? new Date(dashboardCheckoutDate.value) : null;
                let nights = 1;
                if (checkinDate && checkoutDate && checkoutDate >= checkinDate) {
                    nights = Math.max(1, Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24)));
                }
                const gross = nightly * nights;
                const discount = Math.max(0, Number(dashboardDiscountAmount?.value || 0));
                const net = Math.max(0, gross - discount);

                if (dashboardGrossAmount) dashboardGrossAmount.textContent = formatMoney(gross);
                if (dashboardDiscountPreview) dashboardDiscountPreview.textContent = formatMoney(discount);
                if (dashboardNetAmount) dashboardNetAmount.textContent = formatMoney(net);
            };

            [dashboardCheckinDate, dashboardCheckoutDate, dashboardDiscountAmount].forEach((el) => {
                el?.addEventListener('input', computeDashboardAmounts);
                el?.addEventListener('change', computeDashboardAmounts);
            });
            computeDashboardAmounts();

            const setDashboardFeedback = (message, type = 'info') => {
                if (!dashboardClientSearchFeedback) return;
                dashboardClientSearchFeedback.classList.remove('text-danger', 'text-success', 'text-muted', 'text-warning');
                if (type === 'error') dashboardClientSearchFeedback.classList.add('text-danger');
                else if (type === 'success') dashboardClientSearchFeedback.classList.add('text-success');
                else if (type === 'warning') dashboardClientSearchFeedback.classList.add('text-warning');
                else dashboardClientSearchFeedback.classList.add('text-muted');
                dashboardClientSearchFeedback.textContent = message;
            };

            const renderDashboardClients = (clients) => {
                if (!dashboardClientSelect) return;
                dashboardClientSelect.innerHTML = '<option value="">Sélectionner</option>';
                clients.forEach((client) => {
                    const option = document.createElement('option');
                    option.value = String(client.id);
                    option.textContent = client.name;
                    dashboardClientSelect.appendChild(option);
                });

                if (clients.length > 0) {
                    dashboardClientSelect.value = String(clients[0].id);
                }
            };

            let dashboardRemoteDebounce;

            const remoteDashboardSearch = async (term) => {
                const query = term.trim();
                if (!query) {
                    return;
                }

                try {
                    const response = await fetch(`{{ route('clients.search') }}?q=${encodeURIComponent(query)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();
                    const clients = data?.clients || [];
                    if (clients.length > 0) {
                        renderDashboardClients(clients);
                        setDashboardFeedback(`${clients.length} client(s) trouvé(s).`, 'success');
                        return;
                    }
                    setDashboardFeedback('Aucun client trouvé. Utilisez "Nouveau client".', 'warning');
                } catch (error) {
                    setDashboardFeedback('Erreur de recherche client.', 'error');
                }
            };

            dashboardClientSearchInput?.addEventListener('input', () => {
                const term = (dashboardClientSearchInput.value || '').trim().toLowerCase();
                if (!term) {
                    renderDashboardClients(dashboardClients);
                    setDashboardFeedback('', 'info');
                    clearTimeout(dashboardRemoteDebounce);
                    return;
                }

                const matches = dashboardClients.filter((client) => client.name.toLowerCase().includes(term));
                renderDashboardClients(matches);

                if (matches.length > 0) {
                    setDashboardFeedback(`${matches.length} client(s) trouvé(s).`, 'success');
                } else {
                    setDashboardFeedback('Recherche serveur en cours...', 'info');
                }

                clearTimeout(dashboardRemoteDebounce);
                dashboardRemoteDebounce = setTimeout(() => remoteDashboardSearch(term), 250);
            });

            const upsertDashboardClient = (client) => {
                if (!dashboardClientSelect || !client?.id) return;
                let option = [...dashboardClientSelect.options].find((opt) => Number(opt.value) === Number(client.id));
                if (!option) {
                    option = document.createElement('option');
                    option.value = String(client.id);
                    dashboardClientSelect.appendChild(option);
                }
                option.textContent = client.name;
                dashboardClientSelect.value = String(client.id);
            };

            dashboardClientCreateForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitButton = dashboardClientCreateForm.querySelector('button[type="submit"]');
                if (submitButton) submitButton.disabled = true;
                if (dashboardClientCreateFeedback) {
                    dashboardClientCreateFeedback.className = 'small mt-2 text-muted';
                    dashboardClientCreateFeedback.textContent = 'Création en cours...';
                }

                const formData = new FormData(dashboardClientCreateForm);
                try {
                    const response = await fetch(`{{ route('clients.quick-store') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        const firstError = data?.errors ? Object.values(data.errors)[0][0] : 'Erreur de création client.';
                        if (dashboardClientCreateFeedback) {
                            dashboardClientCreateFeedback.className = 'small mt-2 text-danger';
                            dashboardClientCreateFeedback.textContent = firstError;
                        }
                        return;
                    }

                    dashboardClients.push({ id: Number(data.client.id), name: data.client.name });
                    upsertDashboardClient(data.client);
                    if (dashboardClientCreateFeedback) {
                        dashboardClientCreateFeedback.className = 'small mt-2 text-success';
                        dashboardClientCreateFeedback.textContent = 'Client créé et sélectionné.';
                    }
                    dashboardClientCreateForm.reset();
                    bootstrap.Modal.getOrCreateInstance(dashboardCreateClientModalEl).hide();
                    bootstrap.Modal.getOrCreateInstance(reservationModal).show();
                } catch (error) {
                    if (dashboardClientCreateFeedback) {
                        dashboardClientCreateFeedback.className = 'small mt-2 text-danger';
                        dashboardClientCreateFeedback.textContent = 'Erreur serveur. Réessayez.';
                    }
                } finally {
                    if (submitButton) submitButton.disabled = false;
                }
            });
        </script>
    @endif
</x-app-layout>
