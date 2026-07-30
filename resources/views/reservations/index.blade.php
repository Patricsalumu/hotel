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
        }

        .rv-inline-tools {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            min-width: 0;
        }

        .rv-inline-tools .rv-action-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
        }

        .rv-inline-tools .rv-action-row form {
            display: inline-block;
            margin: 0;
        }

        .rv-inline-tools .btn {
            padding: .35rem .5rem;
            font-size: .85rem;
            min-width: 34px;
        }

        /* Calendar styles */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 20px;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
        }

        .calendar-header div {
            padding: 10px;
            background: #f3f4f6;
            border-radius: 4px;
        }

        .calendar-day {
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px;
            min-height: 100px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calendar-day:hover {
            background: #f9fafb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .calendar-day.other-month {
            background: #f9fafb;
            color: #9ca3af;
        }

        .calendar-day.today {
            background: #dbeafe;
            border-color: #3b82f6;
        }

        .calendar-day-number {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .calendar-reservations {
            font-size: 0.75rem;
        }

        .calendar-reservation-item {
            background: #fecaca;
            border-left: 3px solid #dc2626;
            padding: 2px 4px;
            margin-bottom: 2px;
            border-radius: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .calendar-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .view-toggle {
            display: flex;
            gap: 5px;
        }

        .view-toggle button {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .view-toggle button.active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        @media (max-width: 575.98px) {
            .rv-kpi-value {
                font-size: 1.08rem;
            }

            .rv-actions {
                min-width: 180px;
            }

            .rv-inline-tools {
                min-width: 280px;
            }

            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 4px;
            }

            .calendar-day {
                min-height: 80px;
                padding: 4px;
            }
        }
    </style>

    <x-slot name="header">
        <div class="d-flex flex-column gap-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-1">Réservations</h4>
                    <div class="small text-white-50">Suivi complet des séjours, statuts et règlements</div>
                </div>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createReservationModal">Nouvelle réservation</button>
            </div>
            <form class="d-flex flex-wrap gap-2 align-items-end" method="GET">
                <div class="d-flex flex-column" style="min-width: 220px;">
                    <label class="form-label mb-1">Code réservation</label>
                    <input type="text" name="reservation_code" class="form-control form-control-sm" placeholder="Code réservation" value="{{ request('reservation_code') }}">
                </div>
                <div class="d-flex flex-column" style="min-width: 220px;">
                    <label class="form-label mb-1">Client</label>
                    <input type="text" name="client_name" class="form-control form-control-sm" placeholder="Client" value="{{ request('client_name') }}">
                </div>
                <div class="d-flex flex-column" style="min-width: 150px;">
                    <label class="form-label mb-1">Du</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', now()->toDateString()) }}">
                </div>
                <div class="d-flex flex-column" style="min-width: 150px;">
                    <label class="form-label mb-1">Au</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', now()->toDateString()) }}">
                </div>
                <button type="submit" class="btn btn-sm btn-light">Filtrer</button>
            </form>
        </div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @php
        $currency = $hotel->currency ?? 'FC';
        $activeReservations = $reservations->filter(fn ($reservation) => ! $reservation->trashed());
        $pageTotalAmount = $activeReservations->sum(function ($reservation) use ($hotel) {
            $nights = $reservation->computeNights(now(), $hotel->checkout_time);
            $gross = (float) ($reservation->apartment?->price_per_night ?? $reservation->room?->price_per_night ?? 0) * $nights;
            $discount = (float) ($reservation->discount_amount ?? 0);

            return max(0, $gross - $discount);
        });
        $pagePaidAmount = $activeReservations->sum(fn ($reservation) => $reservation->payments->sum('amount'));
        $pageSolde = $pageTotalAmount - $pagePaidAmount;
    @endphp

    <div class="row g-2 mb-3">
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Réservations (page)</div><div class="rv-kpi-value">{{ $activeReservations->count() }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Montant total</div><div class="rv-kpi-value">{{ \App\Support\Money::format($pageTotalAmount, $currency) }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Total payé</div><div class="rv-kpi-value text-success">{{ \App\Support\Money::format($pagePaidAmount, $currency) }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Solde</div><div class="rv-kpi-value {{ $pageSolde > 0 ? 'text-danger' : ($pageSolde < 0 ? 'text-success' : '') }}">{{ \App\Support\Money::format($pageSolde, $currency) }}</div></div></div>
    </div>

    <!-- Tableau -->
    <div id="tableView">

    <div class="modal fade" id="createReservationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('reservations.store') }}">
                    @csrf
                    <input type="hidden" name="creation_source" value="reservations_index">
                    <div class="modal-header"><h5 class="modal-title">Nouvelle réservation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label">Rechercher client</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="clientSearchInput" placeholder="Nom, téléphone, email">
                                    <button class="btn btn-outline-secondary" type="button" id="clientSearchBtn">Rechercher</button>
                                </div>
                                <div class="small text-muted mt-1" id="clientSearchFeedback"></div>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Client</label>
                                <select class="form-select" name="client_id" id="reservationClientSelect" required>
                                    <option value="">Client</option>
                                    @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="openCreateClientModalBtn" data-bs-toggle="modal" data-bs-target="#createClientQuickModal">Nouveau client</button>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Appartement</label>
                                <select class="form-select" name="apartment_id" id="reservationApartmentSelect" required>
                                    <option value="">Sélectionner un appartement</option>
                                    @foreach($bookableApartments as $apartment)
                                        <option value="{{ $apartment->id }}" data-price="{{ (float) $apartment->price_per_night }}">{{ $apartment->name }} - {{ \App\Support\Money::format($apartment->price_per_night, $currency) }}</option>
                                    @endforeach
                                </select>
                                <div class="small text-muted mt-1">
                                    La chambre sera assignée au check-in. Sélectionnez l’appartement souhaité pour la réservation.
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="row g-2">
                                    <div class="col-md-6"><label class="form-label">Date entrée prévue</label><input type="date" class="form-control" id="reservationExpectedCheckinDate" name="expected_checkin_date" value="{{ old('expected_checkin_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required></div>
                                    <div class="col-md-6"><label class="form-label">Date prévue</label><input type="date" class="form-control" id="reservationCheckoutDate" name="expected_checkout_date" value="{{ old('expected_checkout_date') }}" min="{{ now()->toDateString() }}"></div>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label">Réduction</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="reservationDiscountAmount" name="discount_amount" value="{{ old('discount_amount', 0) }}" placeholder="0">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-2 bg-light">
                                    <div class="d-flex justify-content-between"><span>Nuitées</span><strong id="reservationNightsCount">1</strong></div>
                                    <div class="d-flex justify-content-between"><span>Total à payer</span><strong id="reservationGrossAmount">0 {{ $currency }}</strong></div>
                                    <div class="d-flex justify-content-between"><span>Réduction</span><strong id="reservationDiscountPreview">0 {{ $currency }}</strong></div>
                                    <div class="d-flex justify-content-between"><span>Net à payer</span><strong id="reservationNetAmount">0 {{ $currency }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn gh-btn-primary btn-primary">Créer</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createClientQuickModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="createClientQuickForm">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Nouveau client</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nationalité</label>
                                <input type="text" class="form-control" name="nationality">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">N° document</label>
                                <input type="text" class="form-control" name="document_number">
                            </div>
                        </div>
                        <div class="small mt-2" id="clientCreateFeedback"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn gh-btn-primary btn-primary" type="submit">Créer et sélectionner</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="gh-card card table-responsive">
        <table class="table table-hover align-middle mb-0 rv-table">
            <thead class="table-light"><tr><th>Réservation</th><th>Chambre</th><th>Client</th><th>Entrée P</th><th>Entrée R</th><th>Départ P</th><th>Départ R</th><th>Nuitées P</th><th>Nuitées R</th><th>Total P</th><th>Total R</th><th>Payé</th><th>Solde</th><th>Statut</th></tr></thead>
            <tbody>
            @forelse($reservations as $reservation)
                @php
                    $expectedNights = $reservation->computePlannedNights();
                    $actualNights = $reservation->computeNights(now(), $hotel->checkout_time);
                    $plannedGross = (float) ($reservation->apartment?->price_per_night ?? $reservation->room?->price_per_night ?? 0) * $expectedNights;
                    $gross = (float) ($reservation->apartment?->price_per_night ?? $reservation->room?->price_per_night ?? 0) * $actualNights;
                    $discount = (float) ($reservation->discount_amount ?? 0);
                    $plannedNetTotal = max(0, $plannedGross - $discount);
                    $netTotal = max(0, $gross - $discount);
                    $paid = $reservation->payments->sum('amount');
                    $solde = $netTotal - $paid;
                    $derivedPaymentStatus = $paid <= 0 ? 'unpaid' : ($paid >= $netTotal ? 'paid' : 'partial');
                @endphp
                <tr>
                    <td>
                        @if($reservation->trashed())
                            <span class="fw-semibold text-muted">{{ $reservation->reference }}</span>
                        @else
                            <a href="{{ route('reservations.show',$reservation) }}" class="fw-semibold">{{ $reservation->reference }}</a>
                        @endif
                    </td>
                    <td>{{ $reservation->room?->number ? 'Ch. ' . $reservation->room->number : ($reservation->apartment?->name ?? '-') }}</td>
                    <td class="rv-client">{{ $reservation->client->name }}</td>
                    <td>{{ $reservation->expected_checkin_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->checkin_date?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $reservation->expected_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->actual_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $expectedNights }}</td>
                    <td>{{ $actualNights }}</td>
                    <td><span class="fw-semibold">{{ \App\Support\Money::format($plannedNetTotal, $currency) }}</span></td>
                    <td><span class="fw-semibold">{{ \App\Support\Money::format($netTotal, $currency) }}</span></td>
                    <td><span class="text-success fw-semibold">{{ \App\Support\Money::format($paid, $currency) }}</span></td>
                    <td>
                        <span class="fw-semibold {{ $solde > 0 ? 'text-danger' : ($solde < 0 ? 'text-success' : 'text-muted') }}">
                            {{ \App\Support\Money::format($solde, $currency) }}
                        </span>
                    </td>
                    <td>
                        <div class="rv-status">
                            <span class="badge text-bg-{{ $reservation->trashed() ? 'secondary' : ($reservation->status === 'checked_out' ? 'secondary' : ($reservation->status === 'checked_in' ? 'warning' : 'info')) }}">
                                {{ $reservation->trashed() ? 'annulée' : (['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$reservation->status] ?? $reservation->status) }}
                            </span>
                            <span class="badge text-bg-{{ $derivedPaymentStatus === 'paid' ? 'success' : ($derivedPaymentStatus === 'partial' ? 'warning' : 'danger') }}">
                                {{ ['unpaid' => 'non payé', 'partial' => 'partiel', 'paid' => 'payé', 'credit' => 'à crédit'][$derivedPaymentStatus] }}
                            </span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">
                        <div class="gh-empty my-2">Aucune réservation trouvée pour les filtres sélectionnés.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $reservations->links() }}</div>
    </div>

    <script>
        // Calendar state
        let currentMonth = new Date();
        const reservations = @json($calendarReservations ?? []);
        const roomPlanningReservations = @json($roomPlanningReservations ?? []);
        const monthYearEl = document.getElementById('monthYear');
        const calendarGridEl = document.getElementById('calendarGrid');
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');
        const viewButtons = document.querySelectorAll('.view-btn');
        const tableViewEl = document.getElementById('tableView');
        const calendarViewEl = document.getElementById('calendarView');

        function renderCalendar() {
            if (!monthYearEl || !calendarGridEl) {
                return;
            }

            const year = currentMonth.getFullYear();
            const month = currentMonth.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const prevLastDay = new Date(year, month, 0);

            monthYearEl.textContent = `${['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][month]} ${year}`;

            calendarGridEl.innerHTML = '';

            const startDate = firstDay.getDay();
            for (let i = startDate - 1; i >= 0; i--) {
                const day = prevLastDay.getDate() - i;
                const cell = createDayCell(day, true);
                calendarGridEl.appendChild(cell);
            }

            for (let day = 1; day <= lastDay.getDate(); day++) {
                const date = new Date(year, month, day);
                const cell = createDayCell(day, false, date);
                calendarGridEl.appendChild(cell);
            }

            for (let day = 1; calendarGridEl.children.length % 7 !== 0; day++) {
                const cell = createDayCell(day, true);
                calendarGridEl.appendChild(cell);
            }
        }

        function createDayCell(day, isOtherMonth, date = null) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day';

            if (isOtherMonth) {
                cell.classList.add('other-month');
            }

            if (date) {
                const today = new Date();
                if (date.toDateString() === today.toDateString()) {
                    cell.classList.add('today');
                }

                const dayReservations = reservations.filter(r => {
                    const checkin = new Date(r.checkin);
                    const checkout = new Date(r.checkout);
                    return date >= checkin && date <= checkout;
                });

                let html = `<div class="calendar-day-number">${day}</div>`;
                if (dayReservations.length > 0) {
                    html += '<div class="calendar-reservations">';
                    dayReservations.forEach(res => {
                        const icon = res.is_cancelled ? '❌' : '📋';
                        html += `<div class="calendar-reservation-item" title="${res.reference} - Chambre ${res.room_number} - ${res.client_name}">${icon} Chambre ${res.room_number}</div>`;
                    });
                    html += '</div>';
                }
                cell.innerHTML = html;
            } else {
                cell.textContent = day;
            }

            return cell;
        }

        // View toggle
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const view = this.dataset.view;
                if (tableViewEl && calendarViewEl) {
                    tableViewEl.classList.toggle('d-none', view !== 'table');
                    calendarViewEl.classList.toggle('d-none', view !== 'calendar');
                }

                if (view === 'calendar') {
                    renderCalendar();
                }
            });
        });

        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', () => {
                currentMonth.setMonth(currentMonth.getMonth() - 1);
                renderCalendar();
            });
        }

        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', () => {
                currentMonth.setMonth(currentMonth.getMonth() + 1);
                renderCalendar();
            });
        }

        const formatMoney = (value) => {
            const numeric = Number(value || 0);
            return `${numeric.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} {{ $currency }}`;
        };

        const apartmentSelect = document.getElementById('reservationApartmentSelect');
        const checkinInput = document.getElementById('reservationExpectedCheckinDate');
        const checkoutInput = document.getElementById('reservationCheckoutDate');
        const discountInput = document.getElementById('reservationDiscountAmount');
        const grossPreview = document.getElementById('reservationGrossAmount');
        const discountPreview = document.getElementById('reservationDiscountPreview');
        const netPreview = document.getElementById('reservationNetAmount');
        const reservationNightsCount = document.getElementById('reservationNightsCount');

        const parseDateOnly = (value) => {
            if (!value) return null;
            const parsed = new Date(`${value}T00:00:00`);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        };

        const computeReservationAmounts = () => {
            const selectedApartment = apartmentSelect?.selectedOptions?.[0];
            const nightly = Number(selectedApartment?.dataset?.price || 0);

            const checkinDate = checkinInput?.value ? new Date(checkinInput.value) : null;
            const checkoutDate = checkoutInput?.value ? new Date(checkoutInput.value) : null;

            let nights = 1;
            if (checkinDate && checkoutDate && checkoutDate >= checkinDate) {
                nights = Math.max(1, Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24)));
            }

            const gross = nightly * nights;
            const discount = Math.max(0, Number(discountInput?.value || 0));
            const net = Math.max(0, gross - discount);

            if (reservationNightsCount) reservationNightsCount.textContent = String(nights);
            if (grossPreview) grossPreview.textContent = formatMoney(gross);
            if (discountPreview) discountPreview.textContent = formatMoney(discount);
            if (netPreview) netPreview.textContent = formatMoney(net);
        };

        [apartmentSelect, checkinInput, checkoutInput, discountInput].forEach((el) => {
            el?.addEventListener('change', computeReservationAmounts);
            el?.addEventListener('input', computeReservationAmounts);
        });

        computeReservationAmounts();

        const clientSearchInput = document.getElementById('clientSearchInput');
        const clientSearchBtn = document.getElementById('clientSearchBtn');
        const clientSearchFeedback = document.getElementById('clientSearchFeedback');
        const clientSelect = document.getElementById('reservationClientSelect');
        const createClientModalEl = document.getElementById('createClientQuickModal');
        const createClientNameInput = createClientModalEl?.querySelector('input[name="name"]');

        const setFeedback = (element, message, type = 'info') => {
            if (!element) return;
            element.classList.remove('text-danger', 'text-success', 'text-muted', 'text-warning');
            if (type === 'error') element.classList.add('text-danger');
            else if (type === 'success') element.classList.add('text-success');
            else if (type === 'warning') element.classList.add('text-warning');
            else element.classList.add('text-muted');
            element.textContent = message;
        };

        const upsertClientOption = (client) => {
            if (!clientSelect || !client?.id) {
                return;
            }

            let option = [...clientSelect.options].find((opt) => Number(opt.value) === Number(client.id));
            if (!option) {
                option = document.createElement('option');
                option.value = client.id;
                clientSelect.appendChild(option);
            }

            option.textContent = client.name;
            clientSelect.value = String(client.id);
        };

        const initialClients = [...(clientSelect?.options || [])]
            .filter((opt) => opt.value)
            .map((opt) => ({ id: Number(opt.value), name: opt.textContent || '' }));

        const renderClientOptions = (clients, autoSelect = true) => {
            if (!clientSelect) return;

            clientSelect.innerHTML = '<option value="">Client</option>';
            clients.forEach((client) => {
                const option = document.createElement('option');
                option.value = String(client.id);
                option.textContent = client.name;
                clientSelect.appendChild(option);
            });

            if (autoSelect && clients.length > 0) {
                clientSelect.value = String(clients[0].id);
            }
        };

        const filterLocalClients = (q) => {
            const term = q.trim().toLowerCase();
            if (!term) {
                return initialClients;
            }

            return initialClients.filter((client) => client.name.toLowerCase().includes(term));
        };

        let remoteSearchDebounce;

        const remoteSearchClients = async (q, canOpenCreateModal = false) => {
            const query = q.trim();
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
                    renderClientOptions(clients);
                    setFeedback(clientSearchFeedback, `${clients.length} client(s) trouvé(s).`, 'success');
                    return;
                }

                if (canOpenCreateModal) {
                    setFeedback(clientSearchFeedback, 'Aucun client trouvé. Créez un nouveau client.', 'warning');
                    if (createClientNameInput) {
                        createClientNameInput.value = query;
                    }
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('createClientQuickModal')).show();
                } else {
                    setFeedback(clientSearchFeedback, 'Aucun client trouvé pour cette recherche.', 'warning');
                }
            } catch (error) {
                setFeedback(clientSearchFeedback, 'Erreur de recherche client.', 'error');
            }
        };

        clientSearchInput?.addEventListener('input', () => {
            const q = clientSearchInput.value || '';
            const localMatches = filterLocalClients(q);
            renderClientOptions(localMatches, true);

            if (!q.trim()) {
                setFeedback(clientSearchFeedback, '', 'info');
                clearTimeout(remoteSearchDebounce);
                return;
            }

            if (localMatches.length > 0) {
                setFeedback(clientSearchFeedback, `${localMatches.length} client(s) localement.`, 'info');
            } else {
                setFeedback(clientSearchFeedback, 'Recherche serveur en cours...', 'info');
            }

            clearTimeout(remoteSearchDebounce);
            remoteSearchDebounce = setTimeout(() => remoteSearchClients(q, false), 250);
        });

        clientSearchBtn?.addEventListener('click', async () => {
            const q = (clientSearchInput?.value || '').trim();
            if (!q) {
                setFeedback(clientSearchFeedback, 'Saisissez un nom, téléphone ou email.', 'warning');
                return;
            }

            setFeedback(clientSearchFeedback, 'Recherche en cours...', 'info');
            clientSearchBtn.disabled = true;

            await remoteSearchClients(q, true);
            clientSearchBtn.disabled = false;
        });

        const clientCreateForm = document.getElementById('createClientQuickForm');
        const clientCreateFeedback = document.getElementById('clientCreateFeedback');

        clientCreateForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            setFeedback(clientCreateFeedback, 'Création en cours...', 'info');
            const submitButton = clientCreateForm.querySelector('button[type="submit"]');
            if (submitButton) submitButton.disabled = true;

            const formData = new FormData(clientCreateForm);
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
                    setFeedback(clientCreateFeedback, firstError, 'error');
                    return;
                }

                upsertClientOption(data.client);
                initialClients.push({ id: Number(data.client.id), name: data.client.name });
                setFeedback(clientCreateFeedback, 'Client créé et sélectionné.', 'success');
                clientCreateForm.reset();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createClientQuickModal')).hide();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createReservationModal')).show();
            } catch (error) {
                setFeedback(clientCreateFeedback, 'Erreur serveur. Réessayez.', 'error');
            } finally {
                if (submitButton) submitButton.disabled = false;
            }
        });

        createClientModalEl?.addEventListener('hidden.bs.modal', () => {
            setFeedback(clientCreateFeedback, '', 'info');
        });
    </script>

</x-app-layout>
