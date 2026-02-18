<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 14px; }
        .logo { max-height: 64px; margin-bottom: 6px; }
        .company-name { font-size: 18px; font-weight: 700; }
        .title { font-size: 15px; font-weight: 700; margin-top: 8px; }
        .meta { color: #444; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .footer { margin-top: 16px; border-top: 1px solid #d1d5db; padding-top: 8px; font-size: 11px; color: #555; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($logoDataUri))
            <img src="{{ $logoDataUri }}" class="logo" alt="Logo entreprise">
        @endif

        <div class="company-name">{{ $hotel->name }}</div>
        <div class="meta">Adresse: {{ $enterpriseAddress ?: '-' }}</div>
        <div class="meta">Téléphone: {{ $hotel->phone ?: '-' }} | Mail: {{ $enterpriseEmail ?: '-' }}</div>

        <div class="title">Rapport des réservations</div>
        <div class="meta">Date d’export: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Chambre</th>
                <th>Client</th>
                <th>Date d’arrivée</th>
                <th>Départ prévu</th>
                <th class="right">Total</th>
                <th class="right">Payé</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        @forelse($reservations as $r)
            <tr>
                <td>{{ $r->room->number }}</td>
                <td>{{ $r->client->name }}</td>
                <td>{{ $r->checkin_date?->format('Y-m-d') }}</td>
                <td>{{ $r->expected_checkout_date?->format('Y-m-d') }}</td>
                <td class="right">{{ \App\Support\Money::format($r->total_amount, $currency) }}</td>
                <td class="right">{{ \App\Support\Money::format($r->payments->sum('amount'), $currency) }}</td>
                <td>{{ ['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$r->status] ?? $r->status }} / {{ ['unpaid' => 'non payé', 'partial' => 'partiel', 'paid' => 'payé'][$r->payment_status] ?? $r->payment_status }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Aucune réservation pour les filtres sélectionnés.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">Informatisée par Ayanna ERP</div>
</body>
</html>
