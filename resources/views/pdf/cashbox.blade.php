<!doctype html>
<html><head><meta charset="utf-8"><style>body{font-family: DejaVu Sans, sans-serif;font-size:12px} table{width:100%;border-collapse:collapse;margin-bottom:10px} th,td{border:1px solid #ccc;padding:6px}</style></head>
<body>
<h3>Rapport Caisse - {{ $hotel->name }}</h3>
<p>Période: {{ $from->format('Y-m-d') }} à {{ $to->format('Y-m-d') }}</p>
<p>Total entrées: <strong>{{ number_format($payments->sum('amount'),2) }}</strong> | Total sorties: <strong>{{ number_format($expenses->sum('amount'),2) }}</strong> | Net: <strong>{{ number_format($payments->sum('amount') - $expenses->sum('amount'),2) }}</strong></p>
<h4>Entrées</h4>
<table><thead><tr><th>Heure</th><th>Chambre</th><th>Montant</th><th>Méthode</th></tr></thead><tbody>@foreach($payments as $p)<tr><td>{{ $p->created_at }}</td><td>{{ $p->reservation->room->number ?? '-' }}</td><td>{{ number_format($p->amount,2) }}</td><td>{{ ['cash' => 'Espèces', 'mobile' => 'Mobile money', 'card' => 'Carte'][$p->payment_method] ?? $p->payment_method }}</td></tr>@endforeach</tbody></table>
<h4>Sorties</h4>
<table><thead><tr><th>Heure</th><th>Catégorie</th><th>Montant</th><th>Description</th></tr></thead><tbody>@foreach($expenses as $e)<tr><td>{{ $e->created_at }}</td><td>{{ ['carburant' => 'Carburant', 'transport' => 'Transport', 'salaires' => 'Salaires', 'autres' => 'Autres'][$e->category] ?? ucfirst($e->category) }}</td><td>{{ number_format($e->amount,2) }}</td><td>{{ $e->description }}</td></tr>@endforeach</tbody></table>
</body></html>
