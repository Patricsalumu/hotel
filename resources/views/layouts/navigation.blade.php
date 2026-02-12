<nav class="navbar navbar-expand-lg navbar-dark" style="background:#111827; box-shadow: 0 8px 24px rgba(17,24,39,.20);">
    <div class="container gh-shell">
        <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">{{ config('app.name', 'Gestion Hôtelière') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('reservations.*') ? 'active fw-semibold' : '' }}" href="{{ route('reservations.index') }}">Réservations</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('clients.*') ? 'active fw-semibold' : '' }}" href="{{ route('clients.index') }}">Clients</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('cashbox.*') ? 'active fw-semibold' : '' }}" href="{{ route('cashbox.index') }}">Caisse</a></li>
                @if(auth()->user()?->role === 'super_admin')
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('superadmin.*') ? 'active fw-semibold' : '' }}" href="{{ route('superadmin.hotels.index') }}">Super Admin</a></li>
                @endif
                @if(auth()->user()?->role === 'owner')
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('owner.hotels.*') ? 'active fw-semibold' : '' }}" href="{{ route('owner.hotels.index') }}">Hôtel</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('owner.apartments.*') ? 'active fw-semibold' : '' }}" href="{{ route('owner.apartments.index') }}">Appartements</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('owner.rooms.*') ? 'active fw-semibold' : '' }}" href="{{ route('owner.rooms.index') }}">Chambres</a></li>
                @endif
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill" style="background:#1f2937; border:1px solid #3a475d;">{{ strtoupper(auth()->user()->role) }}</span>
                <span class="small text-light-emphasis">{{ auth()->user()->name }}</span>
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-light">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-light" type="submit">Déconnexion</button>
                </form>
            </div>
        </div>
    </div>
</nav>
