# Hiroshima Back Office

Sistem Back Office dan REST API POS untuk Hiroshima Japanese Street Food.

## Fitur Utama
- Laravel 11 + Blade / Tailwind CSS
- REST API POS v1 (Auth, Sync, Order, Shifts, Authorization PIN)
- Backoffice Manajemen: Produk, Varian, Modifiers, Promosi, Meja, Cabang, Kas, Shift, Pengguna & RBAC
- Deteksi Fraud & Audit Log Otomatis

## Persyaratan Sistem
- PHP >= 8.2
- Composer
- MySQL / MariaDB
- Node.js & NPM

## Setup Proyek
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```
