<x-app-layout>
    <x-slot name="header">
        <h4 class="mb-0">Profil</h4>
    </x-slot>

    <div class="row g-3">
        <div class="col-12">
            <div class="card"><div class="card-body">
                    @include('profile.partials.update-profile-information-form')
            </div></div>
        </div>

        <div class="col-12">
            <div class="card"><div class="card-body">
                    @include('profile.partials.update-password-form')
            </div></div>
        </div>

        <div class="col-12">
            <div class="card border-danger"><div class="card-body">
                    @include('profile.partials.delete-user-form')
            </div></div>
        </div>
    </div>
</x-app-layout>
