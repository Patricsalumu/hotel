<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Détails réservation #{{ $reservation->id }}</h4>
                <div class="small text-white-50">Informations séjour et encaissement</div>
            </div>
            <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-light">Retour aux réservations</a>
        </div>
    </x-slot>

    <div class="row g-3">
        @php
            $paidAmount = $reservation->payments->sum('amount');
            $remainingAmount = max(0, (float) $reservation->total_amount - (float) $paidAmount);
        @endphp
        <div class="col-md-8">
            <div class="gh-card card"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="gh-kpi h-100"><div class="gh-kpi-label">Client</div><div class="gh-kpi-value" style="font-size:1.15rem;">{{ $reservation->client->name }}</div></div></div>
                    <div class="col-md-6"><div class="gh-kpi h-100"><div class="gh-kpi-label">Chambre</div><div class="gh-kpi-value" style="font-size:1.15rem;">#{{ $reservation->room->number }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Date d’arrivée</div><div class="fw-semibold">{{ $reservation->checkin_date?->format('Y-m-d') }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Départ prévu</div><div class="fw-semibold">{{ $reservation->expected_checkout_date?->format('Y-m-d') }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Départ réel</div><div class="fw-semibold">{{ $reservation->actual_checkout_date?->format('Y-m-d') ?? '-' }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Total</div><div class="gh-kpi-value">{{ number_format($reservation->total_amount,2) }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Montant déjà payé</div><div class="gh-kpi-value text-success">{{ number_format($paidAmount,2) }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Reste à payer</div><div class="gh-kpi-value text-danger">{{ number_format($remainingAmount,2) }}</div></div></div>
                    <div class="col-md-12"><div class="gh-kpi h-100"><div class="gh-kpi-label">Réservation créée par</div><div class="fw-semibold">{{ $reservation->user?->name ?? $reservation->manager?->name ?? '-' }}</div></div></div>
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
                                <td class="fw-semibold">{{ number_format($payment->amount,2) }}</td>
                                <td>{{ ['cash' => 'Cash', 'mobile' => 'Mobile money', 'card' => 'Carte bancaire'][$payment->payment_method] ?? $payment->payment_method }}</td>
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
                <form method="POST" action="{{ route('payments.store') }}" class="vstack gap-2">
                    @csrf
                    <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                    <input type="number" step="0.01" class="form-control" name="amount" value="{{ $remainingAmount > 0 ? number_format($remainingAmount, 2, '.', '') : '' }}" placeholder="Montant" required>
                    <select class="form-select" name="payment_method" required>
                        <option value="cash" selected>Cash</option>
                        <option value="mobile">Mobile money</option>
                        <option value="card">Carte bancaire</option>
                    </select>
                    <button class="btn gh-btn-primary btn-primary">Valider paiement</button>
                </form>
            </div></div>
        </div>
    </div>
</x-app-layout>
