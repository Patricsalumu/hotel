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
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Caisse</h4>
                <div class="small text-white-50">Suivi des entrées, sorties et solde net</div>
            </div>
            <div class="d-flex gap-2 gh-mobile-stack">
                <a class="btn btn-sm btn-outline-light" href="{{ route('cashbox.accounts', request()->only('from_date', 'to_date')) }}">Gestion comptes</a>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createExpenseModal">Créer dépense</button>
            </div>
        </div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

        @php
            $currency = $hotel->currency ?? 'FC';
        @endphp

    <div class="gh-card card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end cb-toolbar">
            <div class="col-md-3"><label class="form-label">De</label><input type="date" name="from_date" class="form-control" value="{{ $from->format('Y-m-d') }}"></div>
            <div class="col-md-3"><label class="form-label">À</label><input type="date" name="to_date" class="form-control" value="{{ $to->format('Y-m-d') }}"></div>
            <div class="col-md-3">
                <label class="form-label">Compte sortie</label>
                <select name="expense_account_id" class="form-select">
                    <option value="">Tous les comptes</option>
                    @foreach($expenseAccounts as $account)
                        <option value="{{ $account->id }}" @selected((int) request('expense_account_id') === $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2 gh-mobile-stack"><button class="btn gh-btn-primary btn-primary">Filtrer</button><a class="btn btn-outline-dark" href="{{ route('cashbox.pdf', request()->query()) }}">Imprimer PDF</a></div>
        </form>
    </div></div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Entrées</div><div class="cb-kpi-value text-success">{{ \App\Support\Money::format($totalIn, $currency) }}</div></div></div>
        <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Sorties</div><div class="cb-kpi-value text-danger">{{ \App\Support\Money::format($totalOut, $currency) }}</div></div></div>
        <div class="col-md-4"><div class="gh-kpi h-100"><div class="gh-kpi-label">Solde net</div><div class="cb-kpi-value {{ ($totalIn - $totalOut) >= 0 ? 'text-success' : 'text-danger' }}">{{ \App\Support\Money::format($totalIn - $totalOut, $currency) }}</div></div></div>
    </div>

    <div class="modal fade" id="createExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('expenses.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Créer une dépense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Compte</label>
                            <select name="account_id" class="form-select" required>
                                <option value="">Sélectionner un compte</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @if($expenseAccounts->isEmpty())
                                <div class="small text-danger mt-1">Aucun compte disponible. Créez d'abord un compte.</div>
                            @endif
                        </div>
                        <div class="mb-2"><label class="form-label">Montant</label><input type="number" step="0.01" name="amount" class="form-control" placeholder="Montant" required></div>
                        <div class="mb-2"><label class="form-label">Description</label><input type="text" name="description" class="form-control" placeholder="Description"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn gh-btn-primary btn-primary" @disabled($expenseAccounts->isEmpty())>Enregistrer</button></div>
                </form>
            </div>
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
                    <thead class="table-light"><tr><th>Heure</th><th>Compte</th><th>Montant</th><th>Description</th></tr></thead>
                    <tbody>
                        @forelse($expenses as $e)
                            <tr><td>{{ $e->created_at }}</td><td>{{ $e->account?->name ?? '-' }}</td><td>{{ \App\Support\Money::format($e->amount, $currency) }}</td><td>{{ $e->description }}</td></tr>
                        @empty
                            <tr><td colspan="4"><div class="gh-empty my-2">Aucune dépense pour la période sélectionnée.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-app-layout>
