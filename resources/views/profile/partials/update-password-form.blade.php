<section>
    <header>
        <h5>Mot de passe</h5>
        <p class="text-muted small">Utilisez un mot de passe robuste.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-3">
        @csrf
        @method('put')

        <div class="mb-3">
            <label for="update_password_current_password" class="form-label">Mot de passe actuel</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" />
            @if($errors->updatePassword->get('current_password'))<div class="text-danger small mt-1">{{ $errors->updatePassword->first('current_password') }}</div>@endif
        </div>

        <div class="mb-3">
            <label for="update_password_password" class="form-label">Nouveau mot de passe</label>
            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" />
            @if($errors->updatePassword->get('password'))<div class="text-danger small mt-1">{{ $errors->updatePassword->first('password') }}</div>@endif
        </div>

        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label">Confirmer mot de passe</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" />
            @if($errors->updatePassword->get('password_confirmation'))<div class="text-danger small mt-1">{{ $errors->updatePassword->first('password_confirmation') }}</div>@endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-primary">Enregistrer</button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">Enregistré.</span>
            @endif
        </div>
    </form>
</section>
