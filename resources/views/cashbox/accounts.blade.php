<x-app-layout>
    <style>
        .cbm-bar-track {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: #e9edf3;
            overflow: hidden;
        }

        .cbm-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #0d6efd, #3b82f6);
        }

        .cbm-bar-label {
            font-size: .86rem;
            color: #334155;
        }
    </style>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Gestion comptes</h4>
                <div class="small text-white-50">Comptes de dépenses, montants et renommage</div>
            </div>
            <div class="d-flex gap-2 gh-mobile-stack">
                <a class="btn btn-sm btn-outline-light" href="{{ route('cashbox.index') }}">Retour caisse</a>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createExpenseAccountModal">Créer compte</button>
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

    <div class="gh-card card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label">De</label><input type="date" name="from_date" class="form-control" value="{{ $from->format('Y-m-d') }}"></div>
                <div class="col-md-3"><label class="form-label">À</label><input type="date" name="to_date" class="form-control" value="{{ $to->format('Y-m-d') }}"></div>
                <div class="col-md-3 d-flex gap-2 gh-mobile-stack"><button class="btn gh-btn-primary btn-primary">Filtrer</button></div>
            </form>
        </div>
    </div>

    <div class="gh-card card mb-3 table-responsive">
        <div class="card-header">Comptes de dépenses (période sélectionnée)</div>
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Compte</th><th>Dépenses</th><th>Montant total</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($accountStats as $stat)
                <tr>
                    <td>
                        {{ $stat->name }}
                        @if((int) $topAccountId === (int) $stat->id)
                            <span class="badge text-bg-danger ms-1">Le plus coûteux</span>
                        @endif
                    </td>
                    <td>{{ $stat->expenses_count }}</td>
                    <td class="fw-semibold">{{ \App\Support\Money::format($stat->total_amount, $currency) }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#renameExpenseAccountModal{{ $stat->id }}">Renommer</button>
                    </td>
                </tr>

                <div class="modal fade" id="renameExpenseAccountModal{{ $stat->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('expense-accounts.update', $stat->id) }}">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header"><h5 class="modal-title">Renommer le compte</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2">
                                        <label class="form-label">Nom</label>
                                        <input type="text" class="form-control" name="name" value="{{ $stat->name }}" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="2" placeholder="Optionnel">{{ $expenseAccounts->firstWhere('id', $stat->id)?->description }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn gh-btn-primary btn-primary">Enregistrer</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <tr><td colspan="4"><div class="gh-empty my-2">Aucun compte avec dépenses sur la période.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="gh-card card mb-3">
        <div class="card-header">Top comptes (coût)</div>
        <div class="card-body">
            @php
                $topStats = $accountStats->take(5);
                $maxTopAmount = (float) ($topStats->max('total_amount') ?? 0);
            @endphp

            @if($topStats->isEmpty())
                <div class="gh-empty my-2">Aucune donnée de dépense pour tracer le top comptes.</div>
            @else
                <div class="vstack gap-3">
                    @foreach($topStats as $index => $stat)
                        @php
                            $amount = (float) $stat->total_amount;
                            $ratio = $maxTopAmount > 0 ? round(($amount / $maxTopAmount) * 100, 2) : 0;
                        @endphp
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1 cbm-bar-label">
                                <span>{{ $index + 1 }}. {{ $stat->name }}</span>
                                <span class="fw-semibold">{{ \App\Support\Money::format($amount, $currency) }}</span>
                            </div>
                            <div class="cbm-bar-track">
                                <div class="cbm-bar-fill" data-width="{{ $ratio }}" style="width: 0;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="createExpenseAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('expense-accounts.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Créer un compte de dépense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nom du compte</label>
                            <input type="text" class="form-control" name="name" placeholder="Ex: Transport, Carburant" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Optionnel"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn gh-btn-primary btn-primary">Créer compte</button></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.cbm-bar-fill[data-width]').forEach((bar) => {
            const width = Number(bar.getAttribute('data-width') || 0);
            bar.style.width = `${Math.max(0, Math.min(100, width))}%`;
        });
    </script>
</x-app-layout>
