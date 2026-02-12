<section>
    <header>
        <h5 class="text-danger">Supprimer le compte</h5>
        <p class="text-muted small">Action irréversible. Entrez votre mot de passe pour confirmer.</p>
    </header>

    <form method="post" action="{{ route('profile.destroy') }}" class="mt-3">
        @csrf
        @method('delete')
        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" name="password" type="password" class="form-control" placeholder="Mot de passe" />
            @if($errors->userDeletion->get('password'))<div class="text-danger small mt-1">{{ $errors->userDeletion->first('password') }}</div>@endif
        </div>
        <button class="btn btn-danger">Supprimer mon compte</button>
    </form>
</section>
