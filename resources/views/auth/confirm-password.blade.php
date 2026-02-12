<x-guest-layout>
    <h4 class="mb-3">Confirmer le mot de passe</h4>
    <div class="mb-3 text-muted small">
        Zone sécurisée: confirmez votre mot de passe pour continuer.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" />
            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary">Confirmer</button>
        </div>
    </form>
</x-guest-layout>
