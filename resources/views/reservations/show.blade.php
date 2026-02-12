<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Détails réservation #{{ $reservation->id }}</h4></x-slot>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card"><div class="card-body">
                <p><strong>Client:</strong> {{ $reservation->client->name }}</p>
                <p><strong>Chambre:</strong> {{ $reservation->room->number }}</p>
                <p><strong>Date d’arrivée:</strong> {{ $reservation->checkin_date?->format('Y-m-d') }}</p>
                <p><strong>Départ prévu:</strong> {{ $reservation->expected_checkout_date?->format('Y-m-d') }}</p>
                <p><strong>Départ réel:</strong> {{ $reservation->actual_checkout_date?->format('Y-m-d') }}</p>
                <p><strong>Total:</strong> {{ number_format($reservation->total_amount,2) }}</p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6>Encaisser</h6>
                <form method="POST" action="{{ route('payments.store') }}" class="vstack gap-2">
                    @csrf
                    <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                    <input type="number" step="0.01" class="form-control" name="amount" placeholder="Montant" required>
                    <select class="form-select" name="payment_method" required>
                        <option value="cash">Espèces</option>
                        <option value="mobile">Mobile money</option>
                        <option value="card">Carte</option>
                    </select>
                    <button class="btn btn-primary">Valider paiement</button>
                </form>
            </div></div>
        </div>
    </div>
</x-app-layout>
