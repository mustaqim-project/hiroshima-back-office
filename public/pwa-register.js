/**
 * Progressive Web App (PWA) Registration & Install Prompt Engine
 * For Hiroshima Back Office
 */

(function () {
    'use strict';

    // 1. Detect Standalone Display Mode
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    if (isStandalone) {
        document.documentElement.classList.add('pwa-standalone');
    }

    // 2. Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function (registration) {
                    // Registration successful
                    // Check for updates
                    registration.addEventListener('updatefound', function () {
                        const newWorker = registration.installing;
                        if (newWorker) {
                            newWorker.addEventListener('statechange', function () {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New version available
                                    console.log('[PWA] Versi baru Hiroshima Back Office tersedia.');
                                }
                            });
                        }
                    });
                })
                .catch(function (error) {
                    console.warn('[PWA] Pendaftaran Service Worker gagal:', error);
                });
        });
    }

    // 3. BeforeInstallPrompt (Chrome, Edge, Android, Windows)
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        // Prevent default mini-infobar
        e.preventDefault();
        deferredPrompt = e;
        window.deferredPwaPrompt = e;

        // Check if user recently dismissed
        const dismissedAt = localStorage.getItem('hiroshima_pwa_dismissed_at');
        const now = Date.now();
        if (dismissedAt && now - parseInt(dismissedAt, 10) < 7 * 24 * 60 * 60 * 1000) {
            // Dismissed within last 7 days
            return;
        }

        // Render Install Button in Header / Navigation
        renderInstallUI();
    });

    window.addEventListener('appinstalled', function () {
        console.log('[PWA] Aplikasi Hiroshima Back Office berhasil dipasang!');
        deferredPrompt = null;
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.remove();
        const headerBtn = document.getElementById('pwa-header-install-btn');
        if (headerBtn) headerBtn.remove();
        const sidebarItem = document.getElementById('sidebar-pwa-item');
        if (sidebarItem) sidebarItem.style.display = 'none';
        const pageBtn = document.getElementById('pwaInstallBtn');
        if (pageBtn) pageBtn.style.display = 'none';
    });

    function renderInstallUI() {
        if (isStandalone) return;

        // Ensure NO button is ever injected into topbar/header actions
        const oldHeaderBtn = document.getElementById('pwa-header-install-btn');
        if (oldHeaderBtn) oldHeaderBtn.remove();

        // 1. Show in sidebar if sidebar element exists (Back Office layout)
        const sidebarItem = document.getElementById('sidebar-pwa-item');
        if (sidebarItem) {
            sidebarItem.style.display = 'block';
        }

        // 2. Show dedicated landing page / in-page button if exists
        const pageBtn = document.getElementById('pwaInstallBtn') || document.querySelector('[data-pwa-install]');
        if (pageBtn) {
            pageBtn.style.display = 'inline-flex';
        }
    }

    function triggerInstall() {
        if (!deferredPrompt) {
            // Check if iOS
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            if (isIOS) {
                alert('Untuk memasang di iPhone/iPad:\n1. Ketuk tombol Bagikan (Share) di Safari\n2. Pilih "Tambahkan ke Layar Utama" (Add to Home Screen)');
            } else {
                alert('Aplikasi siap dipasang melalui menu browser (ikon Install di baris alamat).');
            }
            return;
        }

        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function (choiceResult) {
            if (choiceResult.outcome === 'accepted') {
                console.log('[PWA] Pengguna menyetujui instalasi PWA.');
            } else {
                console.log('[PWA] Pengguna membatalkan instalasi PWA.');
                localStorage.setItem('hiroshima_pwa_dismissed_at', Date.now().toString());
            }
            deferredPrompt = null;
        });
    }

    // Expose global trigger for custom buttons
    window.hiroshimaInstallPWA = triggerInstall;
})();
