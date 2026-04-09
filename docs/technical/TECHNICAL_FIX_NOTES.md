# Technical Fix Notes

Dokumen ini menggabungkan fix-report yang sebelumnya terpisah.

## 1. Activity Log Timeline Fix (2026-04-06)

Masalah:
- Timeline aktivitas tidak menampilkan perubahan dari beberapa model utama.

Akar masalah:
- Model masih memakai trait audit metadata (`HasAudit`) tanpa trait logging event (`LogsActivity`).

Perbaikan:
- Menambahkan `LogsActivity` pada model terkait sehingga event `created/updated/deleted` masuk ke tabel `activity_logs`.

Dampak:
- Timeline activity pada detail itinerary kembali menampilkan jejak perubahan secara konsisten.

## 2. Sidebar Collapse Scope Fix (2026-02-13)

Masalah:
- Error Alpine scope saat toggle collapse sidebar (`sidebarCollapsed is not defined`).

Akar masalah:
- Nested `x-data` menutup akses state parent.

Perbaikan:
- Gunakan akses parent scope (`$parent.sidebarCollapsed`) dan `x-effect` untuk sinkronisasi state submenu.

Dampak:
- Toggle collapse/expand sidebar stabil tanpa error console.

## Referensi Kode

- `app/Traits/LogsActivity.php`
- `resources/views/components/activity-timeline.blade.php`
- `resources/views/layouts/master.blade.php`

## Catatan Governance

Detail historis perubahan tetap dicatat resmi di:
- `VOYEX_CRM_SYSTEM_ROADMAP.md` bagian `CHANGELOG (LATEST)`
