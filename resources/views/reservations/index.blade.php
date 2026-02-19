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

        .rv-inline-tools {
            display: flex;
            gap: .35rem;
            flex-wrap: nowrap;
            align-items: center;
            white-space: nowrap;
            min-width: 320px;
        }
        .rv-inline-tools form {
            display: inline-block;
            margin: 0;
        }

        .rv-inline-tools > :not(.modal) {
            display: inline-block !important;
            margin-right: .2rem;
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
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Réservations</h4>
                <div class="small text-white-50">Suivi complet des séjours, statuts et règlements</div>
            </div>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createReservationModal">Nouvelle réservation</button>
        </div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    {{-- shared summary for the current page/filter --}}
    @if(!empty($sharePageText))
        <div class="alert alert-info d-flex justify-content-between align-items-center gap-2 gh-mobile-stack">
            <span id="shareText">{{ $sharePageText }}</span>
            <textarea id="shareMessageRaw" class="d-none">{{ $sharePageMessage ?? $sharePageText }}</textarea>
            <div class="d-flex gap-2 gh-mobile-stack">
                @php
                    $summaryText = $sharePageMessage ?? $sharePageText;
                    $summaryWhatsAppUrl = !empty($whatsAppPhone)
                        ? 'https://wa.me/' . $whatsAppPhone . '?text=' . urlencode($summaryText)
                        : 'https://wa.me/?text=' . urlencode($summaryText);
                @endphp
                <a class="btn btn-sm btn-outline-success" target="_blank"
                   href="{{ $summaryWhatsAppUrl }}">
                    WhatsApp
                </a>
                <!-- <button class="btn btn-sm btn-outline-primary" 
                    onclick="navigator.share ? navigator.share({text: document.getElementById('shareMessageRaw').value}) : alert('Partage non supporté')">
                    Partager
                </button> -->
                <button class="btn btn-sm btn-outline-secondary" 
                    onclick="navigator.clipboard.writeText(document.getElementById('shareMessageRaw').value)">
                    Copier
                </button>
            </div>
        </div>
    @endif

    @php
        $currency = $hotel->currency ?? 'FC';
        $pageTotalAmount = $reservations->sum('total_amount');
        $pagePaidAmount = $reservations->sum(fn ($reservation) => $reservation->payments->sum('amount'));
        $pageRemainingAmount = max(0, $pageTotalAmount - $pagePaidAmount);
    @endphp

    <div class="row g-2 mb-3">
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Réservations (page)</div><div class="rv-kpi-value">{{ $reservations->count() }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Montant total</div><div class="rv-kpi-value">{{ \App\Support\Money::format($pageTotalAmount, $currency) }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Total payé</div><div class="rv-kpi-value text-success">{{ \App\Support\Money::format($pagePaidAmount, $currency) }}</div></div></div>
        <div class="col-md-3 col-6"><div class="rv-kpi h-100"><div class="rv-kpi-label">Reste à payer</div><div class="rv-kpi-value text-danger">{{ \App\Support\Money::format($pageRemainingAmount, $currency) }}</div></div></div>
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
            <div class="view-toggle">
                <button class="view-btn active" data-view="table">Tableau</button>
                <button class="view-btn" data-view="calendar">Calendrier</button>
            </div>
        </div>
    </div>

    <!-- Calendrier -->
    <div id="calendarView" class="gh-card card mb-3 d-none">
        <div class="card-body">
            <div class="calendar-controls">
                <button id="prevMonth" class="btn btn-sm btn-outline-secondary">← Mois précédent</button>
                <h5 id="monthYear" class="mb-0"></h5>
                <button id="nextMonth" class="btn btn-sm btn-outline-secondary">Mois suivant →</button>
            </div>
            <div class="calendar-header">
                <div>Dim</div>
                <div>Lun</div>
                <div>Mar</div>
                <div>Mer</div>
                <div>Jeu</div>
                <div>Ven</div>
                <div>Sam</div>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
        </div>
    </div>

    <!-- Tableau -->
    <div id="tableView">

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
            <thead class="table-light"><tr><th>Réservation</th><th>Chambre</th><th>Client</th><th>Date d’arrivée</th><th>Départ prévu</th><th>Départ réel</th><th>Nuitées</th><th>Total</th><th>Payé</th><th>Reste</th><th>Suivi</th></tr></thead>
            <tbody>
            @forelse($reservations as $reservation)
                @php
                    $nights = $reservation->computeNights(now(), $hotel->checkout_time);
                    $paid = $reservation->payments->sum('amount');
                    $remaining = max(0, $reservation->total_amount - $paid);
                @endphp
                <tr>
                    <td><a href="{{ route('reservations.show',$reservation) }}" class="fw-semibold">RES-{{ $reservation->id }}</a></td>
                    <td>{{ $reservation->room->number }}</td>
                    <td class="rv-client">{{ $reservation->client->name }}</td>
                    <td>{{ $reservation->checkin_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->expected_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->actual_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ $nights }}</td>
                    <td><span class="fw-semibold">{{ \App\Support\Money::format($reservation->total_amount, $currency) }}</span></td>
                    <td><span class="text-success fw-semibold">{{ \App\Support\Money::format($paid, $currency) }}</span></td>
                    <td><span class="text-danger fw-semibold">{{ \App\Support\Money::format($remaining, $currency) }}</span></td>
                    <td>
                        <div class="rv-inline-tools">
                        <span class="badge text-bg-{{ $reservation->status === 'checked_out' ? 'secondary' : ($reservation->status === 'checked_in' ? 'warning' : 'info') }}">{{ ['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$reservation->status] ?? $reservation->status }}</span>
                        <span class="badge text-bg-{{ $reservation->payment_status === 'paid' ? 'success' : ($reservation->payment_status === 'partial' ? 'warning' : 'danger') }}">{{ ['unpaid' => 'non payé', 'partial' => 'partiel', 'paid' => 'payé'][$reservation->payment_status] ?? $reservation->payment_status }}</span>

                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#downloadInvoiceModal{{ $reservation->id }}" title="Télécharger facture">⬇</button>

                        @if($remaining > 0)
                            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $reservation->id }}" title="Payer" aria-label="Payer">💳</button>
                        @else
                            <button class="btn btn-sm btn-outline-dark" type="button" title="Déjà payé" aria-label="Déjà payé" disabled>💳</button>
                        @endif

                        @if($reservation->status === 'reserved')
                            <form method="POST" action="{{ route('reservations.update',$reservation) }}">@csrf @method('PUT')<input type="hidden" name="action" value="checkin"><button class="btn btn-sm btn-outline-success" title="Check-in" aria-label="Check-in">✅</button></form>
                        @else
                            <button class="btn btn-sm btn-outline-success" type="button" title="Check-in" aria-label="Check-in" disabled>✅</button>
                        @endif
                        <form method="POST" action="{{ route('reservations.update',$reservation) }}">@csrf @method('PUT')<input type="hidden" name="action" value="checkout"><button class="btn btn-sm btn-outline-danger" title="Check-out" aria-label="Check-out">↩</button></form>
                        </div>

                        <div class="modal fade" id="downloadInvoiceModal{{ $reservation->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Télécharger facture</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body d-grid gap-2">
                                        <a class="btn btn-outline-secondary" href="{{ route('reservations.invoice.pdf', ['reservation' => $reservation->id, 'paper' => 'a4']) }}">Format A4</a>
                                        <a class="btn btn-outline-secondary" href="{{ route('reservations.invoice.pdf', ['reservation' => $reservation->id, 'paper' => '80mm']) }}">Format 80mm</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($remaining > 0)
                            <div class="modal fade" id="paymentModal{{ $reservation->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('payments.store') }}">
                                            @csrf
                                            <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Paiement – Chambre {{ $reservation->room->number }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2">
                                                    <label class="form-label">Montant</label>
                                                    <input type="number" step="0.01" min="0.01" class="form-control" name="amount" value="{{ number_format($remaining, 2, '.', '') }}" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Mode de paiement</label>
                                                    <select class="form-select" name="payment_method" required>
                                                        <option value="cash" selected>Cash</option>
                                                        <option value="card">Carte bancaire</option>
                                                        <option value="mobile">Mobile money</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <button class="btn gh-btn-primary btn-primary">Valider paiement</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
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
    </div>

    <script>
        // Calendar state
        let currentMonth = new Date();
        const reservations = @json($calendarReservations ?? []);

        function renderCalendar() {
            const year = currentMonth.getFullYear();
            const month = currentMonth.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const prevLastDay = new Date(year, month, 0);
            
            document.getElementById('monthYear').textContent = `${['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][month]} ${year}`;
            
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';
            
            const startDate = firstDay.getDay();
            for (let i = startDate - 1; i >= 0; i--) {
                const day = prevLastDay.getDate() - i;
                const cell = createDayCell(day, true);
                grid.appendChild(cell);
            }
            
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const date = new Date(year, month, day);
                const cell = createDayCell(day, false, date);
                grid.appendChild(cell);
            }
            
            for (let day = 1; grid.children.length % 7 !== 0; day++) {
                const cell = createDayCell(day, true);
                grid.appendChild(cell);
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
                
                const dateStr = date.toISOString().split('T')[0];
                const dayReservations = reservations.filter(r => {
                    const checkin = new Date(r.checkin);
                    const checkout = new Date(r.checkout);
                    return date >= checkin && date <= checkout;
                });
                
                let html = `<div class="calendar-day-number">${day}</div>`;
                if (dayReservations.length > 0) {
                    html += '<div class="calendar-reservations">';
                    dayReservations.forEach(res => {
                        html += `<div class="calendar-reservation-item" title="Chambre ${res.room_number} - ${res.client_name}">📋 Chambre ${res.room_number}</div>`;
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
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const view = this.dataset.view;
                document.getElementById('tableView').classList.toggle('d-none', view !== 'table');
                document.getElementById('calendarView').classList.toggle('d-none', view !== 'calendar');
                
                if (view === 'calendar') {
                    renderCalendar();
                }
            });
        });
        
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() - 1);
            renderCalendar();
        });
        
        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() + 1);
            renderCalendar();
        });
    </script>

</x-app-layout>
