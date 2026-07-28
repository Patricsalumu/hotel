<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="mb-1">Administration - Chambres</h4>
            <div class="small text-white-50">Création, édition et placement visuel des chambres</div>
        </div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="gh-card card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Créer une chambre</h5>
                <div class="small text-muted">Ajoutez une chambre à un appartement existant.</div>
            </div>
            <button type="button" class="btn gh-btn-primary btn-primary" data-bs-toggle="modal" data-bs-target="#createRoomModal">Créer une chambre</button>
        </div>
    </div>

    <div class="modal fade" id="createRoomModal" tabindex="-1" aria-labelledby="createRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRoomModalLabel">Nouvelle chambre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('owner.rooms.store') }}">
                    @csrf
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Appartement</label>
                            <select name="apartment_id" class="form-select" required>
                                <option value="">Sélectionnez un appartement</option>
                                @foreach($apartments as $apartment)
                                    <option value="{{ $apartment->id }}" @selected((string) old('apartment_id') === (string) $apartment->id)>{{ $apartment->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Numéro</label>
                            <input name="number" class="form-control" placeholder="Numéro" value="{{ old('number') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn gh-btn-primary btn-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="gh-card card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Chambres</span>
            <div class="small text-muted">Modifier ou supprimer une chambre existante.</div>
        </div>
        <div class="card-body table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Appartement</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $r)
                        <tr>
                            <td>{{ $r->number }}</td>
                            <td>{{ $r->apartment->name }}</td>
                            <td>{{ ['available' => 'Libre', 'reserved' => 'Réservée', 'occupied' => 'Occupée'][$r->status] ?? $r->status }}</td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary me-2 edit-room-button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editRoomModal"
                                    data-id="{{ $r->id }}"
                                    data-apartment-id="{{ $r->apartment_id }}"
                                    data-number="{{ $r->number }}"
                                    data-dimension="{{ $r->dimension ?? '120x80' }}"
                                >Modifier</button>
                                <form method="POST" action="{{ route('owner.rooms.destroy', $r) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette chambre ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="gh-empty my-2">Aucune chambre créée pour le moment.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoomModalLabel">Modifier la chambre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" id="editRoomModalForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Appartement</label>
                            <select name="apartment_id" id="editRoomApartment" class="form-select" required>
                                @foreach($apartments as $apartment)
                                    <option value="{{ $apartment->id }}">{{ $apartment->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Numéro</label>
                            <input name="number" id="editRoomNumber" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn gh-btn-primary btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const updateRoomBaseUrl = "{{ url('/owner/rooms') }}";
        const editRoomModalForm = document.getElementById('editRoomModalForm');
        const editRoomApartment = document.getElementById('editRoomApartment');
        const editRoomNumber = document.getElementById('editRoomNumber');

        document.querySelectorAll('.edit-room-button').forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const apartmentId = button.dataset.apartmentId;
                const number = button.dataset.number;

                editRoomModalForm.action = `${updateRoomBaseUrl}/${id}`;
                editRoomApartment.value = apartmentId;
                editRoomNumber.value = number;
            });
        });
    </script>
</x-app-layout>
