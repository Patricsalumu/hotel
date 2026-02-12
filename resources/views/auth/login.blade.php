<x-guest-layout>
    <h4 class="mb-3">Connexion</h4>
    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="form-check mb-3">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label">Se souvenir de moi</label>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a class="small" href="{{ route('password.request') }}">
                Mot de passe oublié ?
            </a>
            <button class="btn btn-primary" type="submit">Se connecter</button>
        </div>
    </form>
</x-guest-layout>
