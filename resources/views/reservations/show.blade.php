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
        <div class="col-md-8">
            <div class="gh-card card"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="gh-kpi h-100"><div class="gh-kpi-label">Client</div><div class="gh-kpi-value" style="font-size:1.15rem;">{{ $reservation->client->name }}</div></div></div>
                    <div class="col-md-6"><div class="gh-kpi h-100"><div class="gh-kpi-label">Chambre</div><div class="gh-kpi-value" style="font-size:1.15rem;">#{{ $reservation->room->number }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Date d’arrivée</div><div class="fw-semibold">{{ $reservation->checkin_date?->format('Y-m-d') }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Départ prévu</div><div class="fw-semibold">{{ $reservation->expected_checkout_date?->format('Y-m-d') }}</div></div></div>
                    <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Départ réel</div><div class="fw-semibold">{{ $reservation->actual_checkout_date?->format('Y-m-d') ?? '-' }}</div></div></div>
                    <div class="col-md-12"><div class="gh-kpi h-100"><div class="gh-kpi-label">Total</div><div class="gh-kpi-value">{{ number_format($reservation->total_amount,2) }}</div></div></div>
                </div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="gh-card card"><div class="card-body">
                <h6 class="mb-3">Encaisser</h6>
                <form method="POST" action="{{ route('payments.store') }}" class="vstack gap-2">
                    @csrf
                    <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                    <input type="number" step="0.01" class="form-control" name="amount" placeholder="Montant" required>
                    <select class="form-select" name="payment_method" required>
                        <option value="cash">Espèces</option>
                        <option value="mobile">Mobile money</option>
                        <option value="card">Carte</option>
                    </select>
                    <button class="btn gh-btn-primary btn-primary">Valider paiement</button>
                </form>
            </div></div>
        </div>
    </div>
</x-app-layout>
