<!doctype html>
<html><head><meta charset="utf-8"><style>body{font-family: DejaVu Sans, sans-serif;font-size:12px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #ccc;padding:6px}</style></head>
<body>
<h3>Rapport Réservations - {{ $hotel->name }}</h3>
<table>
<thead><tr><th>Chambre</th><th>Client</th><th>Date d’arrivée</th><th>Départ prévu</th><th>Total</th><th>Payé</th><th>Statut</th></tr></thead>
<tbody>
@foreach($reservations as $r)
<tr>
<td>{{ $r->room->number }}</td>
<td>{{ $r->client->name }}</td>
<td>{{ $r->checkin_date?->format('Y-m-d') }}</td>
<td>{{ $r->expected_checkout_date?->format('Y-m-d') }}</td>
<td>{{ number_format($r->total_amount,2) }}</td>
<td>{{ number_format($r->payments->sum('amount'),2) }}</td>
<td>{{ ['reserved' => 'réservée', 'checked_in' => 'en cours', 'checked_out' => 'terminée'][$r->status] ?? $r->status }} / {{ ['unpaid' => 'non payé', 'partial' => 'partiel', 'paid' => 'payé'][$r->payment_status] ?? $r->payment_status }}</td>
</tr>
@endforeach
</tbody>
</table>
</body></html>
