<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            :root {
                --gh-bg: #f4f2ee;
                --gh-ink: #1f2937;
                --gh-muted: #6b7280;
                --gh-gold: #c8a96a;
                --gh-panel: #ffffff;
                --gh-shadow: 0 10px 30px rgba(17, 24, 39, 0.08);
            }

            body {
                background:
                    radial-gradient(circle at 0% 0%, rgba(200, 169, 106, 0.18), transparent 36%),
                    radial-gradient(circle at 100% 0%, rgba(17, 24, 39, 0.08), transparent 34%),
                    var(--gh-bg);
                color: var(--gh-ink);
                padding-top: 72px;
            }

            .gh-shell {
                max-width: 1320px;
            }

            .gh-header {
                background: linear-gradient(120deg, #111827, #1f2937 65%, #2f3d53);
                color: #fff;
                border-radius: 16px;
                box-shadow: var(--gh-shadow);
            }

            .gh-card {
                border: 0;
                border-radius: 14px;
                box-shadow: var(--gh-shadow);
                background: var(--gh-panel);
            }

            .gh-card .card-header {
                background: transparent;
                border-bottom: 1px solid #eef1f5;
                font-weight: 600;
            }

            .gh-btn-primary {
                background: #111827;
                border-color: #111827;
            }

            .gh-btn-primary:hover {
                background: #0b1220;
                border-color: #0b1220;
            }

            .gh-kpi {
                border-radius: 14px;
                border: 1px solid #eceff3;
                padding: 1rem;
                background: #fff;
            }

            .gh-kpi-label {
                color: var(--gh-muted);
                font-size: .82rem;
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            .gh-kpi-value {
                font-size: 1.6rem;
                font-weight: 700;
                line-height: 1.2;
            }

            .gh-room-card {
                border: 1px solid #e9edf3;
                border-radius: 12px;
                background: #fff;
                transition: transform .15s ease, box-shadow .15s ease;
            }

            .gh-room-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 24px rgba(17, 24, 39, 0.08);
            }

            .gh-progress {
                height: 1.1rem;
                border-radius: 999px;
                background: #eceff3;
            }

            .form-control,
            .form-select {
                border-color: #d7dee7;
                min-height: 42px;
            }

            .form-control:focus,
            .form-select:focus {
                border-color: #94a3b8;
                box-shadow: 0 0 0 0.2rem rgba(17, 24, 39, 0.10);
            }

            .table thead th {
                white-space: nowrap;
                font-size: .84rem;
                color: #475569;
                text-transform: uppercase;
                letter-spacing: .02em;
            }

            .gh-empty {
                border: 1px dashed #cdd5df;
                border-radius: 12px;
                background: #fafbfc;
                color: #64748b;
                padding: 1rem;
                text-align: center;
            }

            .gh-room-board {
                height: 560px;
            }

            @media (max-width: 991.98px) {
                .gh-header {
                    border-radius: 12px;
                    padding: 1rem !important;
                }

                .gh-kpi-value {
                    font-size: 1.35rem;
                }

                .gh-card .card-body,
                .gh-card .card-header {
                    padding-left: .85rem;
                    padding-right: .85rem;
                }

                .table thead th {
                    font-size: .78rem;
                }
            }

            @media (max-width: 575.98px) {
                body {
                    padding-top: 66px;
                }

                .gh-shell {
                    padding-left: .65rem;
                    padding-right: .65rem;
                }

                .gh-header {
                    margin-top: .65rem !important;
                    margin-bottom: .8rem !important;
                }

                h4 {
                    font-size: 1.1rem;
                }

                .btn,
                .btn-sm {
                    min-height: 40px;
                }

                .gh-mobile-stack {
                    flex-direction: column !important;
                    align-items: stretch !important;
                }

                .gh-mobile-stack .btn,
                .gh-mobile-stack a {
                    width: 100%;
                }

                .gh-room-board {
                    height: 420px;
                }
            }
        </style>
    </head>
    <body>
        <div class="min-vh-100">
            @include('layouts.navigation')

            <main class="container gh-shell pb-4">
                @if (isset($header))
                    <header class="gh-header px-4 py-3 mb-4 mt-3">
                        {{ $header }}
                    </header>
                @endif
                {{ $slot }}
            </main>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @include('layouts.partials.submit-guard')
    </body>
</html>
