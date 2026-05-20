# Phase 12A-FIX - Production Blockers & High Priority Fixes

Date: 2026-05-21

## Scope

Fixes terbatas pada blocker dan high priority readiness, tanpa menambah fitur bisnis baru.

## Applied Fixes

1. Payment status standardization sync:
- `config/statuses.php` pada `payment.options` disamakan dengan model/runtime saat ini:
  - `pending`, `waiting_confirmation`, `confirmed`, `rejected`, `cancelled`.

2. Route utilitas location resolver diperketat:
- Route `location/resolve-google-map` sekarang wajib permission:
  - `location.resolve_google_map`.

3. Permission dan role mapping diperbarui:
- Tambah global permission `location.resolve_google_map` di `PermissionSeeder`.
- Assign ke role yang relevan operasional/sales:
  - Manager, Marketing, Director, Reservation
  - (Administrator/Super Admin ikut via mekanisme full/default permission).

## Production Notes

1. Pastikan production env menggunakan:
- `APP_ENV=production`
- `APP_DEBUG=false`

2. Jalankan hardening deploy:
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`

3. Payment integration tests masih perlu MySQL test DB karena beberapa migration legacy memakai `ALTER ... MODIFY`.
