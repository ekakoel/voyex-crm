# Technical Fix Notes

Last Updated: 2026-04-17

Dokumen ini menggabungkan fix-report teknis lintas modul yang berdampak ke arsitektur/standar.

## 1. Activity Log Timeline Fix (2026-04-06)

Masalah:
- Timeline aktivitas tidak menampilkan perubahan dari beberapa model utama.

Akar masalah:
- Model memakai trait audit metadata (`HasAudit`) tanpa trait event logging (`LogsActivity`).

Perbaikan:
- Menambahkan `LogsActivity` pada model terkait sehingga event `created/updated/deleted` masuk ke tabel `activity_logs`.

Dampak:
- Timeline activity pada detail itinerary kembali menampilkan jejak perubahan konsisten.

## 2. Sidebar Collapse Scope Fix (2026-02-13)

Masalah:
- Error Alpine scope saat toggle collapse sidebar (`sidebarCollapsed is not defined`).

Akar masalah:
- Nested `x-data` menutup akses state parent.

Perbaikan:
- Gunakan akses parent scope (`$parent.sidebarCollapsed`) dan `x-effect` untuk sinkronisasi state submenu.

Dampak:
- Toggle collapse/expand sidebar stabil tanpa error console.

## 3. Permission Matrix Hardening (2026-04-17)

Masalah:
- Sebagian flow non-CRUD masih mengandalkan role hardcode/fallback.

Perbaikan:
- Dashboard redirect dan workflow approval dipindah ke permission-first.
- Policy CRUD utama diseragamkan ke `module.{module}.{action}`.
- Route aksi khusus (`services.*`, quotation special actions) diselaraskan dengan `module.permission`.
- Alias middleware role-based tidak terpakai dibersihkan.

Dampak:
- Akses sistem lebih konsisten, terukur, dan sepenuhnya dapat dikontrol dari matrix permission.

## 4. Database Safety Clarification (2026-04-17)

Temuan:
- Risiko DB kosong terjadi saat test menggunakan `RefreshDatabase` ke DB yang sama dengan `.env`.

Tindakan dokumentasi:
- Menetapkan rule wajib `.env.testing` terpisah.
- Menambahkan guard dokumentasi untuk command destruktif (`migrate:fresh`, `db:wipe`, `migrate:refresh`).

## Referensi Kode

- `app/Traits/LogsActivity.php`
- `resources/views/components/activity-timeline.blade.php`
- `app/Http/Controllers/DashboardRedirectController.php`
- `app/Http/Middleware/EnsureModulePermission.php`
- `app/Policies/*Policy.php`

## Catatan Governance

Status detail dan perubahan harian resmi tetap dicatat di:
- `VOYEX_CRM_SYSTEM_ROADMAP.md` bagian `CHANGELOG (LATEST)`
