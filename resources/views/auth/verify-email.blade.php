<x-guest-layout>
    <h4 class="mb-3">Vérification email</h4>
    <div class="mb-3 text-muted small">
        Vérifiez votre email via le lien reçu. Si nécessaire, renvoyez un nouveau lien.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">
            Un nouveau lien de vérification a été envoyé.
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Renvoyer l’email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger">Déconnexion</button>
        </form>
    </div>
</x-guest-layout>
