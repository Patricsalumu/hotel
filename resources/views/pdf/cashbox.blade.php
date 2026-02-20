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
        .summary { margin: 10px 0; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .section-title { font-weight: 700; margin-top: 10px; }
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

        <div class="title">Rapport de caisse</div>
        <div class="meta">Période: {{ $from->format('Y-m-d') }} à {{ $to->format('Y-m-d') }}</div>
        <div class="meta">Date d’export: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="summary">
        Total entrées: <strong>{{ \App\Support\Money::format($payments->sum('amount'), $currency) }}</strong>
        | Total sorties: <strong>{{ \App\Support\Money::format($expenses->sum('amount'), $currency) }}</strong>
        | Net: <strong>{{ \App\Support\Money::format($payments->sum('amount') - $expenses->sum('amount'), $currency) }}</strong>
    </div>

    <div class="section-title">Entrées</div>
    <table>
        <thead>
            <tr>
                <th>Heure</th>
                <th>Chambre</th>
                <th class="right">Montant</th>
                <th>Méthode</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $p)
            <tr>
                <td>{{ $p->created_at }}</td>
                <td>{{ $p->reservation->room->number ?? '-' }}</td>
                <td class="right">{{ \App\Support\Money::format($p->amount, $currency) }}</td>
                <td>{{ ['cash' => 'Espèces', 'mobile' => 'Mobile money', 'card' => 'Carte'][($p->payment_method)] ?? $p->payment_method }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">Aucune entrée pour la période sélectionnée.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-title">Sorties</div>
    <table>
        <thead>
            <tr>
                <th>Heure</th>
                <th>Compte</th>
                <th class="right">Montant</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        @forelse($expenses as $e)
            <tr>
                <td>{{ $e->created_at }}</td>
                <td>{{ $e->account?->name ?? '-' }}</td>
                <td class="right">{{ \App\Support\Money::format($e->amount, $currency) }}</td>
                <td>{{ $e->description }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">Aucune dépense pour la période sélectionnée.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">Informatisée par Ayanna ERP</div>
</body>
</html>
