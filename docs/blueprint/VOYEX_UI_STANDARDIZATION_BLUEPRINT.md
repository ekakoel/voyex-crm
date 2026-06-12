# VOYEX UI Standardization Blueprint

Last Updated: 2026-05-21
Scope: UI documentation only

## 1. Tujuan UI Standardization
- Menyamakan pola UI lintas module agar user tidak perlu belajar ulang setiap halaman.
- Meningkatkan kecepatan operasional melalui struktur halaman yang konsisten.
- Menurunkan risiko error operasional dengan visibility status, lock, dan action yang jelas.
- Menjadi acuan implementasi bertahap UI refactor setelah operational workflow selesai.

## 2. Prinsip UI VOYEX CRM
- Konsisten: struktur halaman dan komponen sama lintas module.
- Jelas: status workflow, lock state, dan next action terlihat langsung.
- Efisien: minim klik untuk action utama.
- Aman: action sensitif hanya tampil sesuai permission dan status.
- Ringan: hindari query berlebih dan render berulang yang tidak perlu.

## 3. Standar Index Page
1. Page Header
2. KPI/Summary Cards jika relevan
3. Filter Bar
4. Status Tabs jika relevan
5. Data Table
6. Pagination
7. Empty State
8. Action Dropdown

### 3a. Main Baseline Filter Contract (Wajib)
- Baseline resmi filter index: `Inquiries` module.
- Kontrak wajib:
  - satu filter card utama (tanpa nested card),
  - tidak ada sidebar khusus pada halaman index modul; layout index wajib full-width,
  - filter card wajib selalu tampil di mobile, tablet, dan desktop,
  - pola compact toolbar,
  - hasil data wajib punya table desktop dan card list mobile bila kontennya berupa daftar record,
  - AJAX filter + AJAX pagination (`data-service-filter-*`),
  - minimum 3 karakter untuk text search,
  - tombol `Reset` style `secondary`, tinggi sejajar input, radius setara `app-input`.
- Field filter per modul boleh berbeda sesuai kebutuhan domain, namun style/UX contract harus tetap sama.

## 4. Standar Detail Page
1. Page Header
2. Workflow Stepper
3. Status / Lock Alert
4. Quick Action Panel
5. Main Information Card
6. Related Data Cards
7. Item Table
8. Financial Summary jika relevan
9. Related Documents
10. Activity Timeline
11. Revision / Adjustment History jika relevan

## 5. Standar Form Page
1. Page Header
2. Section Cards
3. Field grouping
4. Required indicator
5. Helper text
6. Validation error display
7. Sticky action footer
8. Cancel button
9. Save button
10. Save & Continue jika relevan

## 6. Standar Workflow Stepper
- Posisi: bagian atas detail page, di bawah page header.
- Menampilkan step sesuai module lifecycle.
- Wajib menandai: completed step, current step, dan upcoming step.
- Label harus menggunakan `ui_phrase()` untuk konsistensi i18n.

## 7. Standar Action Panel
- Berisi action utama sesuai status saat ini.
- Action disusun berdasarkan prioritas workflow.
- Action yang tidak valid harus disembunyikan atau disabled + alasan.
- Action destructive wajib konfirmasi.

## 8. Standar Status Badge
- Semua status menggunakan komponen badge reusable.
- Label status konsisten dengan status matrix.
- Warna mengikuti standar:
  - `draft` / `created` = gray
  - `pending` / `waiting` = yellow
  - `validated` / `confirmed` = blue
  - `approved` / `customer_approved` = green
  - `in_operation` = indigo
  - `completed` / `closed` / `paid` = emerald
  - `cancelled` / `lost` / `void` = red
  - `revised` / `adjustment` = purple

## 9. Standar Empty State
- Menampilkan:
  - Judul singkat kondisi kosong.
  - Penjelasan tindakan yang bisa dilakukan user.
  - CTA (opsional) ke action create/filter reset.
- Konsisten di desktop table view dan mobile card view.

## 10. Standar Table
- Header kolom jelas dan konsisten antar module sejenis.
- Action column di kanan.
- Status wajib dalam badge.
- Mobile fallback menggunakan card list jika tabel terlalu lebar.
- Table wrapper wajib responsive (`overflow-x-auto`).

## 11. Standar Responsive Layout
- Desktop: gunakan grid main + sidebar sesuai konteks module.
- Tablet/mobile: sidebar turun ke bawah atau collapse.
- Komponen action dan filter tetap mudah dijangkau di layar kecil.

## 12. Standar Permission-based Action Visibility
- Action hanya tampil jika user memiliki permission.
- Action juga harus valid terhadap status workflow.
- Hindari menampilkan action yang pasti gagal karena guard status.

## 13. Standar Performance UI
- Hindari query tambahan dari Blade loop (N+1).
- Gunakan eager loading dan agregasi di controller/service.
- Gunakan komponen reusable agar markup lebih ringkas dan maintainable.
- Hindari render data berat jika tidak ditampilkan.

## 14. Standar Map & Location
- Semua module yang memakai Google Maps URL / koordinat harus mengikuti `docs/standards/map-location-standard.md`.
- Dilarang menulis ulang script Leaflet baru per module jika kebutuhan hanya berbeda icon, title, atau hint.
- Gunakan satu engine map shared dan satu pola autofill shared agar perilaku URL map, koordinat, dan reverse geocoding tetap konsisten.
