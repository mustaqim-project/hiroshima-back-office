# Hiroshima Back Office

Sistem Back Office dan REST API POS untuk Hiroshima Japanese Street Food.

## Domain & Endpoint
- **Domain Web / Back Office**: `https://hiroshima.web.id`
- **REST API POS v1**: `https://hiroshima.web.id/api/v1`

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
