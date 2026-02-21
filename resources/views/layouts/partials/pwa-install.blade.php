<style>
    .pwa-install-btn {
        position: fixed;
        right: 14px;
        bottom: 14px;
        z-index: 1080;
        border-radius: 999px;
        display: none;
    }
</style>

<button type="button" id="pwaInstallBtn" class="btn btn-dark pwa-install-btn">Installer l'app</button>

<script>
    (function () {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        window.addEventListener('load', function () {
            navigator.serviceWorker.register("{{ asset('sw.js') }}").catch(function () {});
        });

        let deferredPrompt = null;
        const installBtn = document.getElementById('pwaInstallBtn');

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredPrompt = event;
            if (installBtn) {
                installBtn.style.display = 'inline-flex';
            }
        });

        installBtn?.addEventListener('click', async function () {
            if (!deferredPrompt) {
                return;
            }

            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            installBtn.style.display = 'none';
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            if (installBtn) {
                installBtn.style.display = 'none';
            }
        });
    })();
</script>
