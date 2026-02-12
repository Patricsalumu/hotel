<x-guest-layout>
    <h4 class="mb-3">Mot de passe oublié</h4>
    <div class="mb-3 text-muted small">
        Saisissez votre email pour recevoir un lien de réinitialisation.
    </div>

    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus />
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary">Envoyer le lien</button>
        </div>
    </form>
</x-guest-layout>
