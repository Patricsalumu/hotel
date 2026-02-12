<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Caisse</h4></x-slot>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">De</label><input type="date" name="from_date" class="form-control" value="{{ $from->format('Y-m-d') }}"></div>
            <div class="col-md-3"><label class="form-label">À</label><input type="date" name="to_date" class="form-control" value="{{ $to->format('Y-m-d') }}"></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary">Filtrer</button><a class="btn btn-outline-dark" href="{{ route('cashbox.pdf', request()->query()) }}">Imprimer PDF</a></div>
        </form>
    </div></div>

    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="card"><div class="card-body">Entrées: <strong>{{ number_format($totalIn,2) }}</strong></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body">Sorties: <strong>{{ number_format($totalOut,2) }}</strong></div></div></div>
    </div>

    <div class="alert alert-secondary">Total journalier (net): <strong>{{ number_format($totalIn - $totalOut, 2) }}</strong></div>

    <div class="card mb-3">
        <div class="card-header">Ajouter dépense</div>
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <select name="category" class="form-select" required>
                        <option value="carburant">Carburant</option>
                        <option value="transport">Transport</option>
                        <option value="salaires">Salaires</option>
                        <option value="autres">Autres</option>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Montant" required></div>
                <div class="col-md-5"><input type="text" name="description" class="form-control" placeholder="Description"></div>
                <div class="col-md-2"><button class="btn btn-danger w-100">Enregistrer</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6"><div class="card table-responsive"><div class="card-header">Entrées (paiements)</div><table class="table mb-0"><thead><tr><th>Heure</th><th>Chambre</th><th>Montant</th><th>Méthode</th></tr></thead><tbody>@foreach($payments as $p)<tr><td>{{ $p->created_at }}</td><td>{{ $p->reservation->room->number ?? '-' }}</td><td>{{ number_format($p->amount,2) }}</td><td>{{ ['cash' => 'Espèces', 'mobile' => 'Mobile money', 'card' => 'Carte'][$p->payment_method] ?? $p->payment_method }}</td></tr>@endforeach</tbody></table></div></div>
        <div class="col-md-6"><div class="card table-responsive"><div class="card-header">Sorties (dépenses)</div><table class="table mb-0"><thead><tr><th>Heure</th><th>Catégorie</th><th>Montant</th><th>Description</th></tr></thead><tbody>@foreach($expenses as $e)<tr><td>{{ $e->created_at }}</td><td>{{ ['carburant' => 'Carburant', 'transport' => 'Transport', 'salaires' => 'Salaires', 'autres' => 'Autres'][$e->category] ?? ucfirst($e->category) }}</td><td>{{ number_format($e->amount,2) }}</td><td>{{ $e->description }}</td></tr>@endforeach</tbody></table></div></div>
    </div>
</x-app-layout>
