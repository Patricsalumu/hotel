<section>
    <header>
        <h5>Informations du profil</h5>
        <p class="text-muted small">Mettez à jour votre nom et votre email.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-3">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="small mt-2 text-muted">
                        Email non vérifié.
                        <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">Renvoyer l’email de vérification</button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-success small">Un nouveau lien de vérification a été envoyé.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-primary">Enregistrer</button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small">Enregistré.</span>
            @endif
        </div>
    </form>
</section>
