        <title>{{ config('app.name', 'Gestion Hôtelière') }}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-5 text-center">
                            <h1 class="h3 mb-2">{{ config('app.name', 'Gestion Hôtelière') }}</h1>
                            <p class="text-muted mb-4">Application de gestion d’hôtel multi-appartements.</p>

                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                                <a href="{{ route('login') }}" class="btn btn-primary px-4">Se connecter</a>
                            </div>
                            <p class="small text-muted mt-3 mb-0">Création de compte via Super Admin uniquement.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
