<nav class="navbar navbar-expand-lg bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name', 'Gestion Hôtelière') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('reservations.index') }}">Réservations</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('clients.index') }}">Clients</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('cashbox.index') }}">Caisse</a></li>
                @if(auth()->user()?->role === 'super_admin')
                    <li class="nav-item"><a class="nav-link" href="{{ route('superadmin.hotels.index') }}">Super Admin</a></li>
                @endif
                @if(auth()->user()?->role === 'owner')
                    <li class="nav-item"><a class="nav-link" href="{{ route('owner.hotels.index') }}">Hôtel</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('owner.apartments.index') }}">Appartements</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('owner.rooms.index') }}">Chambres</a></li>
                @endif
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-secondary">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger" type="submit">Déconnexion</button>
                </form>
            </div>
        </div>
    </div>
</nav>
