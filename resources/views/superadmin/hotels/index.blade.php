<x-app-layout>
    <style>
        .sa-kpi {
            border: 1px solid #e7ebf1;
            border-radius: 12px;
            padding: .8rem 1rem;
            background: #fff;
        }

        .sa-kpi-label {
            color: #64748b;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .sa-kpi-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }
    </style>

    <x-slot name="header">
        <div>
            <h4 class="mb-1">Super Admin - Hôtels</h4>
            <div class="small text-white-50">Provisioning des hôtels, utilisateurs et associations</div>
        </div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="row g-2 mb-3">
        <div class="col-md-4 col-6"><div class="sa-kpi h-100"><div class="sa-kpi-label">Hôtels</div><div class="sa-kpi-value">{{ $hotels->total() }}</div></div></div>
        <div class="col-md-4 col-6"><div class="sa-kpi h-100"><div class="sa-kpi-label">Utilisateurs</div><div class="sa-kpi-value">{{ $users->count() }}</div></div></div>
        <div class="col-md-4 col-12"><div class="sa-kpi h-100"><div class="sa-kpi-label">Owners</div><div class="sa-kpi-value">{{ $owners->count() }}</div></div></div>
    </div>

    <div class="gh-card card mb-3" id="card-create-user">
        <div class="card-header">Créer un utilisateur</div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.users.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><label class="form-label">Nom</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
                <div class="col-md-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="{{ old('email') }}" required></div>
                <div class="col-md-2"><label class="form-label">Rôle</label><select class="form-select" name="role" required><option value="owner" @selected(old('role', 'owner') === 'owner')>Owner</option><option value="manager" @selected(old('role') === 'manager')>Manager</option></select></div>
                <div class="col-md-2"><label class="form-label">Mot de passe</label><input type="password" class="form-control" name="password" required></div>
                <div class="col-md-2"><label class="form-label">Confirmer</label><input type="password" class="form-control" name="password_confirmation" required></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn gh-btn-primary btn-primary">Créer utilisateur</button></div>
            </form>
        </div>
    </div>

    <div class="gh-card card mb-3" id="card-create-hotel">
        <div class="card-header">Créer un hôtel</div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.hotels.store') }}" class="row g-2" enctype="multipart/form-data">
                @csrf
                <div class="col-md-4"><label class="form-label">Nom hôtel</label><input class="form-control" name="hotel_name" value="{{ old('hotel_name') }}" required></div>
                <div class="col-md-3"><label class="form-label">Adresse</label><input class="form-control" name="address" value="{{ old('address') }}"></div>
                <div class="col-md-2"><label class="form-label">Ville</label><input class="form-control" name="city" value="{{ old('city') }}"></div>
                <div class="col-md-2"><label class="form-label">Téléphone</label><input class="form-control" name="phone" value="{{ old('phone') }}"></div>
                <div class="col-md-2"><label class="form-label">Logo</label><input type="file" class="form-control" name="image" accept="image/*"></div>
                <div class="col-md-1"><label class="form-label">Checkout</label><input type="time" class="form-control" name="checkout_time" value="{{ old('checkout_time', '12:00') }}" required></div>
                <div class="col-md-4"><label class="form-label">Propriétaire</label><select class="form-select" name="owner_id" required><option value="">Sélectionner</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected((string) old('owner_id') === (string) $owner->id)>{{ $owner->name }} ({{ $owner->email }})</option>@endforeach</select></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn gh-btn-primary btn-primary">Créer hôtel</button></div>
            </form>
        </div>
    </div>

    <div class="gh-card card mb-3" id="card-link-user-hotel">
        <div class="card-header">Lier un utilisateur à un hôtel</div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.users.link') }}" class="row g-2">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Utilisateur</label>
                    <select class="form-select" name="user_id" required>
                        <option value="">Sélectionner</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} ({{ $user->email }}) - {{ $user->role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hôtel</label>
                    <select class="form-select" name="hotel_id" required>
                        <option value="">Sélectionner</option>
                        @foreach($hotels as $hotel)
                            <option value="{{ $hotel->id }}" @selected((string) old('hotel_id') === (string) $hotel->id)>{{ $hotel->name }} ({{ $hotel->city }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Lier</button></div>
            </form>
        </div>
    </div>

    <div class="gh-card card table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Hôtel</th><th>Ville</th><th>Téléphone</th><th>Checkout</th><th>Propriétaire</th><th>Email</th></tr></thead>
            <tbody>
                @forelse($hotels as $hotel)
                    <tr>
                        <td>{{ $hotel->name }}</td>
                        <td>{{ $hotel->city }}</td>
                        <td>{{ $hotel->phone }}</td>
                        <td>{{ $hotel->checkout_time }}</td>
                        <td>{{ $hotel->owner->name ?? '-' }}</td>
                        <td>{{ $hotel->owner->email ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Aucun hôtel pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $hotels->links() }}</div>

    @if($errors->any())
        @php
            $errorFields = $errors->keys();
            $userFields = ['name', 'email', 'role', 'password', 'password_confirmation'];
            $hotelFields = ['hotel_name', 'address', 'city', 'phone', 'checkout_time', 'owner_id'];
            $linkFields = ['user_id', 'hotel_id'];
            $targetCard = null;

            if (collect($errorFields)->intersect($userFields)->isNotEmpty()) {
                $targetCard = 'card-create-user';
            } elseif (collect($errorFields)->intersect($hotelFields)->isNotEmpty()) {
                $targetCard = 'card-create-hotel';
            } elseif (collect($errorFields)->intersect($linkFields)->isNotEmpty()) {
                $targetCard = 'card-link-user-hotel';
            }
        @endphp

        @if($targetCard)
            <script id="superadminTargetCard" type="application/json">@json($targetCard)</script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const targetId = JSON.parse(document.getElementById('superadminTargetCard').textContent || 'null');
                    const target = document.getElementById(targetId);
                    if (!target) return;
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            </script>
        @endif
    @endif
</x-app-layout>
