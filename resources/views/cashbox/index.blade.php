<x-app-layout>
    <style>
        .cb-toolbar .form-label {
            font-size: .74rem;
            color: #64748b;
            margin-bottom: .2rem;
            text-transform: uppercase;
            letter-spacing: .02em;
            font-weight: 600;
        }

        .cb-kpi-value {
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.2;
        }
    </style>

    <x-slot name="header">
        <div>
            <h4 class="mb-1">Caisse</h4>
            <div class="small text-white-50">Suivi des entrées, sorties et solde net</div>
        </div>
    </x-slot>

        @php
            $currency = $hotel->currency ?? 'FC';
        @endphp

    <div class="gh-card card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end cb-toolbar">
            <div class="col-md-3"><label class="form-label">De</label><input type="date" name="from_date" class="form-control" value="{{ $from->format('Y-m-d') }}"></div>
            <div class="col-md-3"><label class="form-label">À</label><input type="date" name="to_date" class="form-control" value="{{ $to->format('Y-m-d') }}"></div>
            <div class="col-md-3 d-flex gap-2 gh-mobile-stack"><button class="btn gh-btn-primary btn-primary">Filtrer</button><a class="btn btn-outline-dark" href="{{ route('cashbox.pdf', request()->query()) }}">Imprimer PDF</a></div>
        </form>
    </div></div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Entrées</div><div class="cb-kpi-value text-success">{{ \App\Support\Money::format($totalIn, $currency) }}</div></div></div>
        <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Sorties</div><div class="cb-kpi-value text-danger">{{ \App\Support\Money::format($totalOut, $currency) }}</div></div></div>
        <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Solde net</div><div class="cb-kpi-value {{ ($totalIn - $totalOut) >= 0 ? 'text-success' : 'text-danger' }}">{{ \App\Support\Money::format($totalIn - $totalOut, $currency) }}</div></div></div>
    </div>

    <div class="gh-card card mb-3">
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
                <div class="col-md-2"><button class="btn gh-btn-primary btn-primary w-100">Enregistrer</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="gh-card card table-responsive">
                <div class="card-header">Entrées (paiements)</div>
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Heure</th><th>Chambre</th><th>Montant</th><th>Méthode</th></tr></thead>
                    <tbody>
                        @forelse($payments as $p)
                            <tr><td>{{ $p->created_at }}</td><td>{{ $p->reservation->room->number ?? '-' }}</td><td>{{ \App\Support\Money::format($p->amount, $currency) }}</td><td>{{ ['cash' => 'Espèces', 'mobile' => 'Mobile money', 'card' => 'Carte'][$p->payment_method] ?? $p->payment_method }}</td></tr>
                        @empty
                            <tr><td colspan="4"><div class="gh-empty my-2">Aucune entrée pour la période sélectionnée.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="gh-card card table-responsive">
                <div class="card-header">Sorties (dépenses)</div>
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Heure</th><th>Catégorie</th><th>Montant</th><th>Description</th></tr></thead>
                    <tbody>
                        @forelse($expenses as $e)
                            <tr><td>{{ $e->created_at }}</td><td>{{ ['carburant' => 'Carburant', 'transport' => 'Transport', 'salaires' => 'Salaires', 'autres' => 'Autres'][$e->category] ?? ucfirst($e->category) }}</td><td>{{ \App\Support\Money::format($e->amount, $currency) }}</td><td>{{ $e->description }}</td></tr>
                        @empty
                            <tr><td colspan="4"><div class="gh-empty my-2">Aucune dépense pour la période sélectionnée.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
