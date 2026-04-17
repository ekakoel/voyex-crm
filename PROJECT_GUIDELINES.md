# Voyex CRM Project Guidelines

Last Updated: 2026-04-17

Dokumen ini berisi aturan kerja wajib. Untuk detail domain/fitur, rujuk `PROJECT_KNOWLEDGE_BASE.md`.

## 1. Mandatory Execution Protocol (Wajib)

1. Pahami penuh flow/code area yang akan diubah sebelum implementasi.
2. Solusi wajib aman dari regression, menjaga performa, dan user-friendly.
3. Perubahan wajib lulus validasi end-to-end pada flow terkait.
4. Jika perubahan lintas modul, sinkronkan dokumentasi lintas dokumen utama.

## 2. Prioritas Dokumen (Jika Terjadi Konflik)

1. `PROJECT_GUIDELINES.md`
2. `PROJECT_KNOWLEDGE_BASE.md`
3. `VOYEX_CRM_SYSTEM_ROADMAP.md`
4. `docs/core/LAYOUT_GUIDE.md`
5. `Dokumen teknis spesifik modul`

## 3. Scope Sistem

Flow bisnis inti:
`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`

Status standar lintas modul transaksi:
- `draft`
- `processed`
- `pending`
- `approved`
- `rejected`
- `final`

Aturan: jika `final`, data view-only (tanpa mutasi).

## 4. Aturan Implementasi Umum

- Gunakan validasi request/controller yang ketat dan eksplisit.
- Gunakan eager loading + pagination untuk list besar.
- Jaga konsistensi policy/permission saat menambah aksi baru.
- Hindari duplikasi data yang tidak perlu (single source of truth per konteks).
- Ikuti standar UI global (`app-card`, `app-table`, `app-input`, `btn-*`).

## 4a. Standar Akses (Wajib)

1. Semua CRUD dan aksi khusus harus dikontrol oleh permission matrix.
2. Hindari hardcode role pada flow bisnis. Role dipakai untuk identity/seed awal, bukan guard utama aksi.
3. Untuk modul CRUD, gunakan kombinasi:
   - `permission:module.{module}.access`
   - `module.permission:{module}`
4. Policy model harus permission-first (`module.{module}.create/read/update/delete`).
5. Untuk modul transaksi berbasis owner (contoh Inquiry, Itinerary, Quotation, dan Booking), aksi mutasi `update/delete` wajib `creator-only`:
   - wajib lolos permission terkait, dan
   - wajib lolos ownership check (`created_by == auth()->id()`).
6. Ownership check harus diletakkan di Policy (bukan di Blade saja), dan UI hanya mengikuti hasil Policy via `@can`.
7. Untuk modul read-only (contoh Invoice saat ini), jangan menambahkan endpoint mutasi (`create/store/edit/update/destroy`) tanpa keputusan arsitektur dan update policy/standar akses.

## 4b. Standar Responsive UI (Wajib)

1. Semua halaman baru wajib mobile-friendly dan tablet-friendly, bukan desktop-only.
2. Halaman existing yang diubah wajib sekalian disesuaikan ke pola responsive project.
3. Untuk list data besar:
   - gunakan table untuk desktop,
   - gunakan card/list responsive untuk mobile/tablet bila table tidak nyaman dipakai.
4. Semua aksi utama (save/update/validate/approve) harus tetap dapat dilakukan di mobile tanpa horizontal-scroll kritis.
5. Jika ada interaksi AJAX per-item, state UI wajib sinkron lintas breakpoint (mobile/tablet/desktop) tanpa reload.
6. Gunakan utility global responsive dari `resources/css/app.css`:
   - `.responsive-data-shell`, `.responsive-data-mobile`, `.responsive-data-desktop`,
   - `.responsive-group-card`, `.responsive-group-header`, `.responsive-item-card`,
   - `.module-kpi-grid`, `.module-action-row`.
7. Implementasi layout data besar wajib memakai pola:
   - card/list untuk mobile-tablet,
   - table untuk desktop (`xl` ke atas), kecuali ada alasan UX yang terdokumentasi.

## 4c. Standar Input Nominal (Wajib)

1. Semua input nominal harga/rate/amount wajib menggunakan komponen `x-money-input`.
2. Badge currency wajib tampil di sisi kiri input (left affix), bukan di kanan.
3. Input nominal tidak boleh ditulis sebagai `<input type="number">` plain pada Blade jika merupakan nilai uang/rate.
4. Normalisasi sebelum submit wajib tetap mengirim angka murni (tanpa separator ribuan) ke backend.
5. Untuk halaman interaktif AJAX, format tampil boleh mengikuti currency aktif, tetapi payload backend wajib tetap konsisten dengan rule modul terkait.
6. Untuk field markup:
   - jika `markup_type = percent`, badge wajib `%`,
   - jika `markup_type = fixed`, badge wajib symbol/code currency aktif.

## 4d. Database Safety (Wajib)

1. Dilarang menjalankan command destruktif (`migrate:fresh`, `db:wipe`, `migrate:refresh`) pada database utama.
2. Testing harus menggunakan database terpisah (`.env.testing`).
3. Sebelum migrasi besar, lakukan backup database.
4. Jika perlu verifikasi destructive command, wajib explicit approval dan di environment non-production.

## 5. Aturan Dokumentasi Wajib

1. Setiap perubahan code wajib dicatat di `VOYEX_CRM_SYSTEM_ROADMAP.md` bagian `CHANGELOG (LATEST)`.
2. Setiap perubahan code wajib mengupdate minimal satu dokumen `.md` yang relevan.
3. Perubahan lintas modul/arsitektur wajib mengupdate `PROJECT_KNOWLEDGE_BASE.md`.

## 6. QA Minimum Setelah Perubahan

1. Cek visual/layout pada halaman yang disentuh.
2. Cek aksi utama (create/edit/list/show) pada flow terkait.
3. Cek empty/error state yang relevan.
4. Pastikan tidak ada error JS console untuk halaman interaktif.
5. Catat ringkas hasil QA pada laporan pekerjaan.
6. Wajib uji minimal 3 viewport setelah perubahan UI:
   - mobile (<= 640px),
   - tablet (641px - 1279px),
   - desktop (>= 1280px).

## 7. Referensi Ringkas

- Sistem menyeluruh: `PROJECT_KNOWLEDGE_BASE.md`
- Roadmap + changelog: `VOYEX_CRM_SYSTEM_ROADMAP.md`
- Peta dokumentasi: `docs/README.md`
- Layout: `docs/core/LAYOUT_GUIDE.md`
- Itinerary detail teknis: `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`, `docs/technical/ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
- UAT quotation approval: `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`
- UAT quotation validation: `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md`
- Ringkasan fix teknis: `docs/technical/TECHNICAL_FIX_NOTES.md`
