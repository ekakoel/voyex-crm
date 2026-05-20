# Phase 12 Go-Live Master

Last Updated: 2026-05-21  
Scope: Konsolidasi seluruh dokumen readiness/go-live Phase 12 agar tidak tersebar dan tidak duplikatif.

## 1. Objective
- Menjadi single source of truth untuk:
  - blocker produksi (12A),
  - end-to-end UAT readiness (12B),
  - staging rehearsal (12C),
  - final staging execution sign-off (12D),
  - final go-live decision.

## 2. Phase Timeline Summary

### 12A - Production Blockers Fix
- Payment status diselaraskan ke canonical runtime:
  - `pending`, `waiting_confirmation`, `confirmed`, `rejected`, `cancelled`, `refunded`, `allocated_as_deposit`.
- Settlement badge mapping disempurnakan di `config/statuses.php`.
- Route utilitas location resolver diperketat:
  - `location/resolve-google-map` wajib permission `locations.resolve_google_map`.
- Baseline safety production:
  - `APP_ENV=production`
  - `APP_DEBUG=false`.

### 12B - End-to-End UAT Validation
- Mayoritas flow business kritikal tervalidasi secara code-path/guard.
- Status hasil:
  - banyak PASS untuk guard flow booking/invoice/payment/operation/adjustment/settlement,
  - sebagian PARTIAL karena membutuhkan bukti manual staging nyata.
- Keputusan: `NO-GO (temporary)` hingga lane staging lengkap tersedia.

### 12C - Staging Rehearsal
- Fokus eksekusi staging lane:
  - MySQL lane,
  - multi-role UAT,
  - deploy dry run,
  - backup/rollback readiness.
- Hasil: blocker utama masih environment/infrastructure execution, bukan bug produk baru.

### 12D - Final Staging Execution Sign-Off
- Verifikasi ulang evidence eksekusi staging.
- MySQL lane masih `BLOCKED` karena dependency/tooling/environment constraints.
- Multi-role UAT manual masih `BLOCKED` tanpa sesi staging nyata.
- Outcome tetap `NO-GO (temporary)` hingga semua mandatory evidence complete.

## 3. Mandatory Go-Live Blockers (Current)
1. MySQL finance/settlement test lane PASS evidence di staging/CI.
2. Multi-role manual UAT evidence lengkap dan signed per role owner.
3. Full deployment dry run + rollback drill evidence pada host staging.

## 4. Deployment Rehearsal Status

### Command Set Target
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
11. `php artisan queue:restart`

### Current Evidence Snapshot
- PASS: `optimize:clear`, `config:cache`, `route:cache`, `view:cache`, `queue:restart`, `route:list`, `about`.
- PARTIAL/BLOCKED: full build/dependency lane (`composer install` dev/no-dev, `npm ci`, `npm run build`) bergantung runner staging/CI terhubung.

## 5. Required Sign-Off Owners
1. Engineering Lead: MySQL lane + deployment rehearsal PASS.
2. Finance Owner: invoice/payment/settlement UAT sign-off.
3. Operations Owner: operation/dispatch/SPK UAT sign-off.
4. Director/Business Owner: final close-gate governance sign-off.

## 6. Final Decision Rule
- Status sekarang: `CONDITIONAL GO (NOT APPROVED YET)` operasionalnya tetap `NO-GO temporary`.
- Promosi ke `GO-LIVE APPROVED` hanya jika ketiga blocker mandatory di atas berstatus DONE dan evidence terdokumentasi.

## 7. Evidence & Supporting Docs
- `docs/technical/GO_LIVE_EXECUTION_PLAYBOOK.md`

## 8. Consolidation Note
- Dokumen-dokumen Phase 12 yang overlap telah dipindahkan ke archive agar struktur dokumentasi lebih ringkas dan tidak membingungkan.
