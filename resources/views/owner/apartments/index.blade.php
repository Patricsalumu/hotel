<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Administration - Appartements</h4></x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Gérer les appartements</h5>
                <div class="small text-muted">Ajoutez, modifiez ou supprimez des appartements.</div>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApartmentModal">Ajouter un appartement</button>
        </div>
    </div>

    <div class="card table-responsive mb-3">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prix</th>
                    <th>Nb chambres</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($apartments as $a)
                    <tr>
                        <td>{{ $a->name }}</td>
                        <td>{{ number_format($a->price_per_night, 2, ',', ' ') }} {{ $currency }}</td>
                        <td>{{ $a->rooms_count }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary me-2 edit-apartment-button"
                                data-bs-toggle="modal"
                                data-bs-target="#editApartmentModal"
                                data-id="{{ $a->id }}"
                                data-name="{{ $a->name }}"
                                data-price="{{ number_format($a->price_per_night, 2, '.', '') }}"
                            >Modifier</button>
                            <form method="POST" action="{{ route('owner.apartments.destroy', $a) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cet appartement ?');">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="createApartmentModal" tabindex="-1" aria-labelledby="createApartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createApartmentModalLabel">Nouvel appartement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('owner.apartments.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'appartement</label>
                            <input class="form-control" name="name" placeholder="Nom appartement" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prix par nuit</label>
                            <input class="form-control" type="number" name="price_per_night" step="0.01" min="0.01" placeholder="Prix" value="{{ old('price_per_night') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editApartmentModal" tabindex="-1" aria-labelledby="editApartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editApartmentModalLabel">Modifier l'appartement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" id="editApartmentForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'appartement</label>
                            <input class="form-control" name="name" id="editApartmentName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prix par nuit</label>
                            <input class="form-control" type="number" step="0.01" min="0.01" name="price_per_night" id="editApartmentPrice" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.edit-apartment-button').forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const name = button.dataset.name;
                const price = button.dataset.price;
                const form = document.getElementById('editApartmentForm');

                form.action = `{{ url('/owner/apartments') }}/${id}`;
                document.getElementById('editApartmentName').value = name;
                document.getElementById('editApartmentPrice').value = price;
            });
        });
    </script>
</x-app-layout>
