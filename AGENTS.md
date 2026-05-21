# AGENTS.md

## Purpose
Panduan untuk AI coding agents agar dapat membantu pengembangan dan otomasi pada project ini secara efektif, khususnya pada halaman index customer agar sidebar "Module Information" menampilkan informasi yang lebih relevan terkait Customer/Agent.

## Sidebar Module Information (Customer Index)
- **Judul sidebar**: Ganti dari "Module Information" menjadi "Customer/Agent Info" atau judul lain yang lebih spesifik sesuai kebutuhan bisnis.
- **Konten sidebar**: Tampilkan informasi ringkasan yang relevan, misal:
  - Total customer pada hasil filter saat ini
  - Jumlah customer aktif vs non-aktif
  - Distribusi tipe customer (individual/company)
  - Negara terbanyak pada hasil filter
- **Sumber data**: Data dapat diambil dari koleksi `$customers` yang sudah dipaginasi di controller.
- **Konvensi**: Gunakan komponen Blade yang reusable, dan gunakan `ui_phrase()` untuk semua label agar konsisten dengan sistem i18n.

## Referensi
- [resources/views/components/module-index-sidebar-info.blade.php](resources/views/components/module-index-sidebar-info.blade.php)
- [resources/views/modules/customers/index.blade.php](resources/views/modules/customers/index.blade.php)
- [app/Models/Customer.php](app/Models/Customer.php)
- [skills/laravel-expert/SKILL.md](skills/laravel-expert/SKILL.md)

## Saran Lanjutan
- Buat skill khusus untuk "customer-index-sidebar" jika ingin logika sidebar lebih kompleks atau dinamis.
- Update dokumentasi jika ada perubahan besar pada struktur sidebar atau data summary yang ditampilkan.
