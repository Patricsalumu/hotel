<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Client: {{ $client->name }}</h4></x-slot>

    <div class="card table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Chambre</th><th>Date d’arrivée</th><th>Date de départ</th><th>Total</th><th>Payé</th><th>Statut</th></tr></thead>
            <tbody>
                @foreach($client->reservations as $reservation)
                <tr>
                    <td>{{ $reservation->room->number ?? '-' }}</td>
                    <td>{{ $reservation->checkin_date?->format('Y-m-d') }}</td>
                    <td>{{ $reservation->actual_checkout_date?->format('Y-m-d') ?? $reservation->expected_checkout_date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($reservation->total_amount,2) }}</td>
                    <td>{{ number_format($reservation->payments->sum('amount'),2) }}</td>
                    <td>{{ ['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$reservation->status] ?? $reservation->status }} / {{ ['unpaid' => 'non payé', 'partial' => 'partiel', 'paid' => 'payé'][$reservation->payment_status] ?? $reservation->payment_status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
