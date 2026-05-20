# Phase 12A-FIX - Production Blockers & High Priority Fixes

Date: 2026-05-21

## Scope

Perbaikan dibatasi pada blocker produksi dan high priority hasil audit Phase 12A.
Tidak menambah modul bisnis baru.

## Fixed

1. Payment status consistency:
- Canonical status diselaraskan di model + config:
  - `pending`, `waiting_confirmation`, `confirmed`, `rejected`, `cancelled`, `refunded`, `allocated_as_deposit`.

2. Settlement badge mapping:
- Status settlement dipetakan eksplisit di `config/statuses.php` agar badge tidak fallback generic:
  - `pending_review`, `outstanding_balance`, `pending_payment`, `pending_adjustment`,
  - `overpaid`, `refund_required`, `deposit_recorded`, `settled`, `closed_blocked`.

3. Location resolver permission hardening:
- Route `location/resolve-google-map` tidak lagi auth-only.
- Wajib permission `locations.resolve_google_map`.
- Permission baru ditambahkan di seeder dan di-assign untuk role:
  - Administrator / Super Admin (via full/default permission),
  - Manager,
  - Reservation,
  - Editor.
- Tidak di-assign default ke Finance / Accountant.

4. Production env safety baseline:
- `.env.example` dibuat production-safe baseline:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `LOG_LEVEL=warning`

## Operational Requirements (Not Code)

1. Production wajib:
- `APP_DEBUG=false`
- tidak expose debug tooling.

2. Verifikasi deployment:
- `php artisan route:list` dan pastikan tidak ada custom debug route aktif selain local.

3. Ignition/debug package routes:
- dikontrol oleh config runtime; pastikan production env + cache sudah benar.

## Testing Constraints

- Payment integration tests tetap skip saat `DB_CONNECTION=sqlite` karena migration legacy memakai MySQL `ALTER ... MODIFY`.
- Jalankan suite finance di MySQL test database untuk validasi penuh.
