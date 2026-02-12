<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Administration - Hôtel</h4></x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="text-muted">Les appartements sont les catégories de produits; les chambres appartiennent à un appartement.</div>
            <div class="d-flex gap-2">
                <a href="{{ route('owner.apartments.index') }}" class="btn btn-outline-primary">Gérer les appartements</a>
                <a href="{{ route('owner.rooms.index') }}" class="btn btn-primary">Gérer les chambres</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('owner.hotels.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><input class="form-control" name="name" placeholder="Nom hôtel" value="{{ $hotel->name ?? '' }}" required></div>
                <div class="col-md-3"><input class="form-control" name="address" placeholder="Adresse" value="{{ $hotel->address ?? '' }}"></div>
                <div class="col-md-2"><input class="form-control" name="city" placeholder="Ville" value="{{ $hotel->city ?? '' }}"></div>
                <div class="col-md-2"><input class="form-control" name="phone" placeholder="Téléphone" value="{{ $hotel->phone ?? '' }}"></div>
                <div class="col-md-1"><input class="form-control" type="time" name="checkout_time" value="{{ $hotel->checkout_time ?? '12:00' }}" required></div>
                <div class="col-md-1"><button class="btn btn-primary w-100">Enregistrer</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
