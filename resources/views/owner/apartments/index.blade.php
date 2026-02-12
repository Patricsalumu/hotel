<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Administration - Appartements</h4></x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('owner.apartments.store') }}" class="row g-2">
            @csrf
            <div class="col-md-5"><input class="form-control" name="name" placeholder="Nom appartement" required></div>
            <div class="col-md-3"><input class="form-control" type="number" name="floor_number" placeholder="Étage" min="0" required></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Ajouter</button></div>
        </form>
    </div></div>

    <div class="card table-responsive"><table class="table mb-0"><thead><tr><th>Nom</th><th>Étage</th><th>Nb chambres</th></tr></thead><tbody>@foreach($apartments as $a)<tr><td>{{ $a->name }}</td><td>{{ $a->floor_number }}</td><td>{{ $a->rooms_count }}</td></tr>@endforeach</tbody></table></div>
</x-app-layout>
