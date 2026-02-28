<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-0">Modifier le client</h4>
                <div class="small text-white-50">Mettez à jour les coordonnées enregistrées</div>
            </div>
            <a class="btn btn-sm btn-light" href="{{ route('clients.index') }}">Retour à la liste</a>
        </div>
    </x-slot>

    <div class="gh-card card">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.update', $client) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $client->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $client->phone) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nationalité</label>
                    <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $client->nationality) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">N° document</label>
                    <input type="text" name="document_number" class="form-control" value="{{ old('document_number', $client->document_number) }}">
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('clients.index') }}">Annuler</a>
                    <button class="btn gh-btn-primary btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
