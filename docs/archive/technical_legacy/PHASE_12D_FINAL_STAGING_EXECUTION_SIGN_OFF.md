# Phase 12D - Final Staging Execution Sign-Off

Date: 2026-05-21

## Scope

Phase 12D fokus menutup blocker akhir go-live dengan evidence eksekusi staging.
Tidak ada fitur baru ditambahkan.

## 1) MySQL Test Lane Execution

Target database: `voyex_crm_test`.

Command executed (current environment):
1. `composer install`
- Result: `BLOCKED`
- Evidence: gagal write cache/vendor temp (`Permission denied`) dan gagal clone dependency ke composer cache path.

2. `php artisan optimize:clear`
- Result: `DONE`

3. `php artisan test --configuration=phpunit.mysql.xml.example`
- Result: `BLOCKED`
- Evidence: `Command "test" is not defined` karena dev dependency belum berhasil diinstall.

Final status lane MySQL: `BLOCKED`.

Required fix instruction (staging/CI):
```sql
CREATE DATABASE voyex_crm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then run on staging/CI runner with writable filesystem:
1. `composer install`
2. `php artisan optimize:clear`
3. `php artisan test --configuration=phpunit.mysql.xml.example`

## 2) Multi-Role Manual UAT Execution

Status: `BLOCKED` (manual staging accounts and interactive UI evidence not available in this environment).

Refer to matrix:
- `docs/technical/STAGING_UAT_EVIDENCE_MATRIX.md`

## 3) Positive Workflow Staging UAT

Status: `BLOCKED` for final execution evidence.
Flow path sudah tersedia di codebase, namun checklist manual 27 langkah belum dapat ditutup tanpa sesi staging nyata.

## 4) Negative Workflow Staging UAT

Status: `BLOCKED` for final manual evidence.
Guard logic sudah tersedia, namun bukti manual pass/fail per kasus belum lengkap di staging.

## 5) Deployment Dry Run

Command evidence from this environment:
- `php artisan queue:restart` -> PASS
- `php artisan route:list` -> output route terbaca
- `php artisan about` -> app boots successfully

Full staging dry run target (git pull/composer no-dev/npm build/migrate/storage link/cache lanes) tetap memerlukan server staging.
Status: `PARTIAL`.

## 6) Scheduler / Queue Verification

Scheduler configured in `app/Console/Kernel.php`:
- `inquiries:notify-reservation-draft-deadline-tomorrow` daily 09:00
- `hotels:sync-status-from-prices` daily 00:10
- `currencies:sync-market-rates` daily 06:00

Queue:
- Queue driver current env: `sync` (lihat `php artisan about`).
- `php artisan queue:restart` command valid.

Status: `DONE (configuration verified)`.

## 7) Backup and Rollback Drill

Runbook tersedia:
- `docs/technical/ROLLBACK_AND_BACKUP_RUNBOOK.md`

Drill execution status in this environment: `BLOCKED` (tidak boleh menjalankan rollback commit/database restore nyata di workspace aktif tanpa staging target terpisah).

## 8) Critical Bugs Found/Fixed

- Critical product bug: tidak ditemukan.
- Blocker utama tetap environment execution untuk sign-off staging.

## Final Outcome

- Readiness tetap belum bisa dipromosikan ke GO final tanpa evidence staging execution nyata.
- Decision tetap `NO-GO (temporary)`.
