<x-app-layout>
    <style>
        .cl-kpi {
            border: 1px solid #e7ebf1;
            border-radius: 12px;
            padding: .8rem 1rem;
            background: #fff;
        }

        .cl-kpi-label {
            color: #64748b;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .cl-kpi-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .cl-table td { vertical-align: middle; }
    </style>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Clients</h4>
                <div class="small text-white-50">Fiches clients et historique de réservations</div>
            </div>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createClientModal">Nouveau client</button>
        </div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @php
        $totalReservationsOnPage = $clients->sum(fn ($client) => $client->reservations->count());
    @endphp

    <div class="row g-2 mb-3">
        <div class="col-md-4 col-6"><div class="cl-kpi h-100"><div class="cl-kpi-label">Clients (page)</div><div class="cl-kpi-value">{{ $clients->count() }}</div></div></div>
        <div class="col-md-4 col-6"><div class="cl-kpi h-100"><div class="cl-kpi-label">Réservations (page)</div><div class="cl-kpi-value">{{ $totalReservationsOnPage }}</div></div></div>
        <div class="col-md-4 col-12"><div class="cl-kpi h-100"><div class="cl-kpi-label">Action rapide</div><div class="cl-kpi-value" style="font-size:1rem;">Créer un nouveau client</div></div></div>
    </div>

    <div class="gh-card card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Gestion clients</div>
            <button class="btn gh-btn-primary btn-primary" data-bs-toggle="modal" data-bs-target="#createClientModal">Nouveau client</button>
        </div>
    </div>

    <div class="modal fade" id="createClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('clients.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Créer client</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label">Nom</label><input class="form-control" name="name" required></div>
                        <div class="mb-2"><label class="form-label">Téléphone</label><input class="form-control" name="phone"></div>
                        <div class="mb-2"><label class="form-label">Email</label><input class="form-control" name="email"></div>
                        <div class="mb-2"><label class="form-label">Nationalité</label><input class="form-control" name="nationality"></div>
                        <div class="mb-2"><label class="form-label">N° document</label><input class="form-control" name="document_number"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn gh-btn-primary btn-primary">Créer</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="gh-card card table-responsive">
        <table class="table table-hover align-middle mb-0 cl-table">
            <thead class="table-light"><tr><th>Nom</th><th>Téléphone</th><th>Email</th><th>Réservations</th><th></th></tr></thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td class="fw-semibold">{{ $client->name }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->email }}</td>
                    <td><span class="badge text-bg-light border">{{ $client->reservations->count() }}</span></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('clients.show',$client) }}">Voir</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5"><div class="gh-empty my-2">Aucun client enregistré pour le moment.</div></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $clients->links() }}</div>
</x-app-layout>
