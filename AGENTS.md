# AGENTS.md

## Purpose
Panduan untuk AI coding agents agar dapat membantu pengembangan dan otomasi pada project ini secara efektif, khususnya agar struktur halaman index mengikuti baseline resmi `Customers / Agents`.

## Struktur Index Baseline (Customers / Agents)
- **Acuan utama**: `resources/views/modules/customers/index.blade.php`.
- **Urutan wajib halaman index**:
  - KPI/Summary Cards di bagian atas jika relevan.
  - Filter card compact tepat di bawah KPI.
  - List data setelah filter, dengan table desktop dan card/list mobile.
  - Pagination dan empty state berada bersama area list.
- **Larangan layout**: Jangan menambahkan sidebar kanan/kiri khusus pada halaman index modul.
- **Filter desktop**: Susun input filter dalam satu baris horizontal selama jumlah field masih wajar.
- **Konvensi**: Gunakan komponen Blade yang reusable, `data-service-filter-*` untuk AJAX filter/pagination, dan `ui_phrase()` untuk semua label agar konsisten dengan sistem i18n.

## Referensi
- [resources/views/modules/customers/index.blade.php](resources/views/modules/customers/index.blade.php)
- [app/Models/Customer.php](app/Models/Customer.php)
- [skills/laravel-expert/SKILL.md](skills/laravel-expert/SKILL.md)

## Saran Lanjutan
- Update dokumentasi jika ada perubahan besar pada struktur baseline index atau data summary yang ditampilkan.
