/**
 * Progressive Web App (PWA) Registration Engine
 * For Hiroshima Back Office
 * Uses native browser install prompts without injecting custom DOM buttons.
 */

(function () {
    'use strict';

    // 1. Bersihkan elemen/tombol warisan lama jika tersimpan di cache
    function removeLegacyButtons() {
        const headerBtn = document.getElementById('pwa-header-install-btn');
        if (headerBtn) headerBtn.remove();
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.remove();
        const sidebarItem = document.getElementById('sidebar-pwa-item');
        if (sidebarItem) sidebarItem.remove();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeLegacyButtons);
    } else {
        removeLegacyButtons();
    }

    // 2. Deteksi Mode Display Standalone
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    if (isStandalone) {
        document.documentElement.classList.add('pwa-standalone');
    }

    // 3. Pendaftaran Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function (registration) {
                    registration.addEventListener('updatefound', function () {
                        const newWorker = registration.installing;
                        if (newWorker) {
                            newWorker.addEventListener('statechange', function () {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
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

    // 4. Menyerahkan instalasi PWA ke fitur bawaan browser (native browser prompt & address bar install icon)
    window.addEventListener('beforeinstallprompt', function (e) {
        // Biarkan browser menampilkan prompt/ikon bawaan secara otomatis (tidak memanggil e.preventDefault dan tidak membuat tombol custom di DOM)
        console.log('[PWA] Browser siap menampilkan instalasi bawaan.');
    });
})();
