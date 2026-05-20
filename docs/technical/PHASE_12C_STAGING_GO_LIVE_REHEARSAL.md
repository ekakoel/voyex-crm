# Phase 12C - Staging Go-Live Rehearsal & Sign-Off

Date: 2026-05-21

## Scope

Phase ini menutup blocker terakhir go-live melalui validasi lane MySQL, UAT multi-role, rehearsal deployment, serta backup/rollback readiness.
Tidak ada penambahan fitur bisnis baru.

## Execution Summary

1. MySQL test lane local: BLOCKED.
2. Manual multi-role UAT staging: BLOCKED (butuh environment staging + akun role terpisah).
3. Deployment dry run command lane: PARTIAL (cache lane PASS, full build/release lane perlu server staging terhubung).
4. Backup/rollback: DOCUMENTED dan siap dijalankan di staging/production.

## 1) MySQL Test Lane

Precondition wajib:
- MySQL server aktif.
- Database test: `voyex_crm_test`.
- `phpunit.mysql.xml.example` digunakan sebagai konfigurasi lane.

Command yang dijalankan di environment ini:
1. `composer install` -> FAIL (permission/cache write constraint environment).
2. `php artisan optimize:clear` -> PASS.
3. `php artisan test --configuration=phpunit.mysql.xml.example` -> FAIL, command `test` belum tersedia karena dev dependency tidak terpasang.

Kesimpulan:
- Status lane MySQL: `BLOCKED (infrastructure/environment)`.
- Bukan indikasi bug logic bisnis pada modul finance/settlement.

## 2) Multi-Role UAT (Staging)

Role target:
- Reservation
- Finance
- Accountant
- Manager
- Director
- Administrator
- Super Admin

Status saat ini:
- Route guard dan permission matrix sudah tersedia dari phase sebelumnya.
- Validasi manual end-to-end per role belum bisa dieksekusi di environment ini karena tidak ada staging user matrix aktif.

Result: `BLOCKED` sampai dijalankan di staging.

## 3) Positive Workflow UAT

Flow target:
Customer/Agent -> Inquiry -> Itinerary -> Quotation -> Validation -> Booking -> Invoice -> Payment -> Operation -> Dispatch/SPK -> Adjustment -> Settlement -> Closed.

Status:
- Validasi struktural code-path + route + guard: tersedia.
- Eksekusi manual penuh di staging: `BLOCKED` menunggu multi-role UAT execution.

## 4) Negative Workflow UAT

Target validasi:
- unpaid booking tidak bisa ready_to_operate
- non service_completed tidak bisa close
- outstanding invoice/pending payment/pending adjustment memblok settlement
- applied adjustment tidak bisa diedit
- closed booking tidak bisa diedit tidak aman
- void/cancelled invoice tidak bisa menerima payment
- rejected/cancelled payment tidak mempengaruhi paid_amount
- non-financial adjustment tidak mutasi invoice/payment

Status:
- Sudah covered di guard/service/test design.
- Eksekusi manual full matrix di staging: `BLOCKED`.

## 5) Deployment Dry Run

Checklist command target staging:
1. `git pull origin main`
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci`
4. `npm run build`
5. `php artisan migrate --force`
6. `php artisan storage:link`
7. `php artisan optimize:clear`
8. `php artisan config:cache`
9. `php artisan route:cache`
10. `php artisan view:cache`
11. `php artisan queue:restart` (jika queue aktif)
12. Verifikasi scheduler cron
13. Verifikasi log

Hasil environment ini:
- `optimize:clear` PASS.
- Cache lane (`config:cache`, `route:cache`, `view:cache`) pernah PASS pada phase sebelumnya.
- Build/release lane penuh belum bisa dibuktikan ulang di environment ini karena constraint dependency/network.

Status: `PARTIAL`.

## 6) Backup & Rollback Verification

Runbook lengkap disediakan di:
- `docs/technical/ROLLBACK_AND_BACKUP_RUNBOOK.md`

Required emergency command tercakup:
- `php artisan down`
- `php artisan up`
- `php artisan optimize:clear`

Status: `DOCUMENTED`, perlu 1x drill di staging untuk sign-off final.

## 7) Critical Bug Fixes in Phase 12C

Tidak ada bug aplikasi kritikal baru yang diperbaiki di phase ini.
Blocker yang tersisa bersifat environment/infrastructure execution.

## Final Status

- Go-live readiness: belum final sign-off.
- Blocker utama: MySQL lane execution + manual staging UAT multi-role + deployment rehearsal penuh di staging.
