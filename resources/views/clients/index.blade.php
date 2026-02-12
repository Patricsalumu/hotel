<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Clients</h4></x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>Gestion clients</div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClientModal">Nouveau client</button>
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
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Créer</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="card table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Nom</th><th>Téléphone</th><th>Email</th><th>Réservations</th><th></th></tr></thead>
            <tbody>
                @foreach($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->reservations->count() }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('clients.show',$client) }}">Voir</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $clients->links() }}</div>
</x-app-layout>
