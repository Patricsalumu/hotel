<x-app-layout>
    @php
        $canCheckin = $reservation->status === 'reserved' && ! $reservation->trashed();
        $canCheckout = ! $reservation->trashed();
    @endphp
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Détails réservation {{ $reservation->reference }}</h4>
                <div class="small text-white-50">Informations séjour et encaissement</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('reservations.invoice.pdf', ['reservation' => $reservation->id, 'paper' => 'a4']) }}" class="btn btn-sm btn-outline-light">Télécharger A4</a>
                    <a href="{{ route('reservations.invoice.pdf', ['reservation' => $reservation->id, 'paper' => '80mm']) }}" class="btn btn-sm btn-outline-light">Télécharger 80mm</a>
                    <a href="{{ $whatsAppInvoiceUrl ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-light">WhatsApp client</a>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center border-start border-light ps-3">
                    @if($canCheckin)
                        <form method="POST" action="{{ route('reservations.update', $reservation) }}" class="m-0">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="checkin">
                            <button class="btn btn-sm btn-outline-success" type="submit" aria-label="Check-in">Check-in</button>
                        </form>
                    @else
                        <button class="btn btn-sm btn-outline-success" type="button" disabled>Check-in</button>
                    @endif

                    @if($canCheckout)
                        <form method="POST" action="{{ route('reservations.update', $reservation) }}" class="m-0">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="checkout">
                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Check-out">Check-out</button>
                        </form>
                    @else
                        <button class="btn btn-sm btn-outline-danger" type="button" disabled>Check-out</button>
                    @endif

                    @if(! $reservation->trashed())
                        <form method="POST" action="{{ route('reservations.update', $reservation) }}" onsubmit="return confirm('Annuler cette réservation ?')" class="m-0">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="cancel">
                            <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Annuler">Annuler</button>
                        </form>
                    @else
                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Annulée</button>
                    @endif
                </div>
                <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-light">Retour aux réservations</a>
            </div>
        </div>
    </x-slot>

    <div class="row g-3">
        @php
            $currency = $reservation->room->apartment->hotel->currency ?? 'FC';
            $nights = $reservation->computeNights(now(), $reservation->room->apartment->hotel->checkout_time);
            $grossAmount = (float) $reservation->room->price_per_night * $nights;
            $discountAmount = (float) ($reservation->discount_amount ?? 0);
            $paidAmount = $reservation->payments->sum('amount');
            $netAmount = max(0, $grossAmount - $discountAmount);
            $solde = $netAmount - (float) $paidAmount;
        @endphp
        <div class="col-md-8">
            <div class="gh-card card"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="gh-kpi h-100"><div class="gh-kpi-label">Client</div><div class="gh-kpi-value" style="font-size:1.15rem;">{{ $reservation->client->name }}</div></div></div>
                    <div class="col-md-6"><div class="gh-kpi h-100"><div class="gh-kpi-label">Chambre</div><div class="gh-kpi-value" style="font-size:1.15rem;">#{{ $reservation->room->number }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Date d’arrivée</div><div class="fw-semibold">{{ $reservation->checkin_date?->format('Y-m-d') }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Départ prévu</div><div class="fw-semibold">{{ $reservation->expected_checkout_date?->format('Y-m-d') }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Départ réel</div><div class="fw-semibold">{{ $reservation->actual_checkout_date?->format('Y-m-d') ?? '-' }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Total à payer</div><div class="gh-kpi-value">{{ \App\Support\Money::format($grossAmount, $currency) }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Réduction</div><div class="gh-kpi-value text-warning">{{ \App\Support\Money::format($discountAmount, $currency) }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Net à payer</div><div class="gh-kpi-value">{{ \App\Support\Money::format($netAmount, $currency) }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Montant déjà payé</div><div class="gh-kpi-value text-success">{{ \App\Support\Money::format($paidAmount, $currency) }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Solde</div><div class="gh-kpi-value {{ $solde > 0 ? 'text-danger' : ($solde < 0 ? 'text-success' : '') }}">{{ \App\Support\Money::format($solde, $currency) }}</div></div></div>
                    <div class="col-md-12"><div class="gh-kpi h-100"><div class="gh-kpi-label">Réservation créée par</div><div class="fw-semibold">{{ $reservation->user?->name ?? $reservation->manager?->name ?? '-' }}</div></div></div>
                    <div class="col-md-12"><div class="gh-kpi h-100"><div class="gh-kpi-label">Statut</div><div class="fw-semibold">{{ $reservation->trashed() ? 'annulée' : (['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$reservation->status] ?? $reservation->status) }}</div></div></div>
                </div>
            </div></div>

            <div class="gh-card card mt-3"><div class="card-body">
                <h6 class="mb-3">Historique des paiements</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Mode</th>
                                <th>Perçu par</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($reservation->payments->sortByDesc('created_at') as $payment)
                            <tr>
                                <td>{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="fw-semibold">{{ \App\Support\Money::format($payment->amount, $currency) }}</td>
                                <td>{{ ['cash' => 'Cash', 'airtelmoney' => 'Airtel money','mpesa' => 'Mpesa', 'card' => 'Carte bancaire'][$payment->payment_method] ?? $payment->payment_method }}</td>
                                <td>{{ $payment->user?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Aucun paiement enregistré.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="gh-card card"><div class="card-body">
                <h6 class="mb-3">Encaisser</h6>
                @if($reservation->trashed())
                    <div class="alert alert-secondary mb-0">Cette réservation est annulée. Paiement indisponible.</div>
                @else
                    <form method="POST" action="{{ route('payments.store') }}" class="vstack gap-2">
                        @csrf
                        <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                        <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount', $solde > 0 ? number_format($solde, 2, '.', '') : '') }}" placeholder="Montant" required>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash" selected>Cash</option>
                            <option value="airtelmoney">Airtel money</option>
                            <option value="mpesa">Mpesa</option>
                            <option value="card">Carte bancaire</option>
                        </select>
                        <button class="btn gh-btn-primary btn-primary">Valider paiement</button>
                    </form>
                @endif
            </div></div>
        </div>
    </div>
</x-app-layout>
