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
    });

    function renderInstallUI() {
        if (isStandalone) return;
        if (document.getElementById('pwa-header-install-btn')) return;

        // Try to add button into header actions if available
        const headerActions = document.querySelector('.header-actions') || document.querySelector('.top-bar-right');
        if (headerActions) {
            const btn = document.createElement('button');
            btn.id = 'pwa-header-install-btn';
            btn.type = 'button';
            btn.className = 'btn-pwa-install';
            btn.innerHTML = `
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2" style="margin-right: 6px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Pasang Aplikasi</span>
            `;
            btn.style.cssText = `
                display: inline-flex;
                align-items: center;
                background: linear-gradient(135deg, #dc2626, #b91c1c);
                color: #ffffff;
                font-size: 12px;
                font-weight: 700;
                padding: 6px 12px;
                border-radius: 8px;
                border: none;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35);
                transition: transform 0.15s ease, opacity 0.15s ease;
                margin-right: 8px;
            `;
            btn.onmouseover = function () { btn.style.transform = 'translateY(-1px)'; };
            btn.onmouseout = function () { btn.style.transform = 'none'; };
            btn.onclick = function () {
                triggerInstall();
            };

            headerActions.prepend(btn);
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
