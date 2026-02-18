<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; }
        body.paper-a4 {
            font-size: 12px;
            margin: 18px;
        }
        body.paper-80 {
            font-size: 11px;
            margin: 6px;
        }
        .title {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .paper-a4 .title {
            font-size: 16px;
        }
        .paper-80 .label {
            font-size: 12px;
        }
        .paper-80 .title {
            font-size: 15px;
        }
        .header {
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .company-name {
            font-weight: 700;
        }
        .paper-a4 .company-name {
            font-size: 18px;
        }
        .paper-80 .company-name {
            font-size: 16px;
        }
        .company-meta {
            color: #444;
            margin-top: 2px;
        }
        .paper-80 .company-meta {
            margin-top: 1px;
        }
        .status {
            font-weight: 700;
            padding: 4px 8px;
            border: 1px solid #999;
            display: inline-block;
            margin-bottom: 10px;
        }
        .paper-80 .status {
            padding: 2px 6px;
            margin-bottom: 6px;
        }
        .block {
            margin-bottom: 10px;
        }
        .paper-80 .block {
            margin-bottom: 7px;
        }
        .row {
            margin-bottom: 4px;
        }
        .label {
            font-weight: 700;
        }
        .logo {
            margin-bottom: 6px;
        }
        .paper-a4 .logo {
            max-height: 70px;
        }
        .paper-80 .logo {
            max-height: 50px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }
        .right {
            text-align: right;
        }
        .small {
            color: #444;
        }
        .paper-a4 .small {
            font-size: 11px;
        }
        .paper-80 .small {
            font-size: 10px;
        }
        .separator {
            border-top: 1px dashed #999;
            margin: 10px 0;
        }
        .paper-80 th,
        .paper-80 td {
            padding: 4px;
        }
        .paper-80 .footer {
            margin-top: 8px;
            padding-top: 6px;
        }
    </style>
</head>
@php $paperClass = $paper === '80mm' ? 'paper-80' : 'paper-a4'; @endphp
<body class="{{ $paperClass }}">
    <div class="header">
        @if(!empty($logoDataUri))
            <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
        @endif
        <div class="company-name">{{ $hotel->name }}</div>
        <div class="company-meta">Adresse: {{ trim(($hotel->address ?? '') . ' ' . ($hotel->city ?? '')) ?: '-' }}</div>
        <div class="company-meta">Téléphone: {{ $hotel->phone ?: '-' }} | Mail: {{ $hotel->owner?->email ?? '-' }}</div>

        <div class="title">Facture N° {{ $reservation->id }}</div>
        <div class="status">{{ $paymentStatusLabel }}</div>
    </div>

    <div class="block">
        <div class="label">Client</div>
        <div class="row">Nom : {{ $reservation->client->name }}</div>
        <div class="row">Email : {{ $reservation->client->email ?: '-' }}</div>
        <div class="row">Téléphone : {{ $reservation->client->phone ?: '-' }}</div>
    </div>

    <div class="block">
        <div class="label">Détails réservation</div>
        @if($paper === '80mm')
            <table>
                <thead>
                    <tr>
                        <th>Logement</th>
                        <th>Nuitées</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ ($reservation->room->apartment->name ?? '-') . ' / Ch. ' . $reservation->room->number }}</td>
                        <td>Prévues: {{ $expectedNights }}<br>Réelles: {{ $actualNights }}</td>
                        <td class="right">{{ \App\Support\Money::format($totalAmount, $currency) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>Montant / nuit</strong></td>
                        <td class="right">{{ \App\Support\Money::format($pricePerNight, $currency) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Appartement</th>
                        <th>Chambre</th>
                        <th>Nuitée prévue</th>
                        <th>Nuitée réelle</th>
                        <th>Montant / nuit</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $reservation->room->apartment->name ?? '-' }}</td>
                        <td>{{ $reservation->room->number }}</td>
                        <td>{{ $expectedNights }}</td>
                        <td>{{ $actualNights }}</td>
                        <td class="right">{{ \App\Support\Money::format($pricePerNight, $currency) }}</td>
                        <td class="right">{{ \App\Support\Money::format($totalAmount, $currency) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="block">
        <div class="row"><span class="label">Créée par :</span> {{ $reservation->user?->name ?? $reservation->manager?->name ?? '-' }}</div>
        <div class="row"><span class="label">Date de création :</span> {{ $reservation->created_at?->format('Y-m-d H:i') }}</div>
        <div class="row"><span class="label">Date d’arrivée :</span> {{ $reservation->checkin_date?->format('Y-m-d') }}</div>
        <div class="row"><span class="label">Date départ prévue :</span> {{ $reservation->expected_checkout_date?->format('Y-m-d') ?? '-' }}</div>
        <div class="row"><span class="label">Date départ réelle :</span> {{ $reservation->actual_checkout_date?->format('Y-m-d') ?? '-' }}</div>
    </div>

    <div class="separator"></div>

    <div class="block">
        <table>
            <tbody>
                <tr>
                    <td><strong>Déjà payé</strong></td>
                    <td class="right"><strong>{{ \App\Support\Money::format($paidAmount, $currency) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Solde (reste à payer)</strong></td>
                    <td class="right"><strong>{{ \App\Support\Money::format($remainingAmount, $currency) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="block">
        <div class="label">Paiements perçus</div>
        @if($paper === '80mm')
            <table>
                <thead>
                    <tr>
                        <th>Détails</th>
                        <th class="right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reservation->payments->sortByDesc('created_at') as $payment)
                    <tr>
                        <td>{{ $payment->created_at?->format('d/m H:i') }}<br>{{ ['cash' => 'Cash', 'mobile' => 'Mobile money', 'card' => 'Carte bancaire'][$payment->payment_method] ?? $payment->payment_method }}<br>{{ $payment->user?->name ?? '-' }}</td>
                        <td class="right">{{ \App\Support\Money::format($payment->amount, $currency) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">Aucun paiement enregistré.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        @else
            <table>
                <thead>
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
                        <td class="right">{{ \App\Support\Money::format($payment->amount, $currency) }}</td>
                        <td>{{ ['cash' => 'Cash', 'mobile' => 'Mobile money', 'card' => 'Carte bancaire'][$payment->payment_method] ?? $payment->payment_method }}</td>
                        <td>{{ $payment->user?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Aucun paiement enregistré.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        @endif
    </div>

    <div class="small">
        Généré le {{ now()->format('Y-m-d H:i') }}
    </div>

    <div class="footer" style="border-top: 1px solid #d1d5db; text-align: center;">
        Informatisée par Ayanna ERP
    </div>
</body>
</html>
