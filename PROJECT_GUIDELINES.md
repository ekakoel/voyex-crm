# Voyex CRM Project Guidelines

Dokumen ini merangkum seluruh informasi inti yang sudah ada di proyek sebagai
patokan utama pengembangan ke depan. Gunakan ini sebagai referensi saat membuat
fitur, melakukan refactor, atau menambah modul baru.

## 1. Tujuan Sistem
Voyex CRM adalah Travel Management System untuk membantu travel agent
mengelola data dan menghasilkan Itinerary, Quotation, dan Invoice secara
efisien, akurat, dan scalable.

## 2. Stack & Lingkungan
- Framework: Laravel 10.50
- PHP: 8.2.x
- Frontend: Vue.js + Tailwind CSS
- Database: MySQL
- Auth: Laravel Breeze + Spatie Permission
- Export: DomPDF (PDF), CSV streaming
- Dev server: Vite

## 3. Alur Bisnis Utama
Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice

Aturan penting:
- Quotation menghitung subtotal, discount, promo, dan final amount.
- Approval dibutuhkan jika diskon atau promo digunakan.
- Booking wajib valid quotation dan travel date.
- Inquiry status otomatis `draft` saat create; field status tidak ditampilkan di form create/edit.
- Inquiry assigned_to otomatis ke user yang login; khusus role Manager/Director dapat memilih assigned_to dari user role Reservation pada form create/edit.
- Inquiry delete diganti Deactivate/Activate (soft delete) dan tombol Delete dihapus dari UI.
- Filters di halaman index menggunakan grid agar setiap input minimal setengah lebar card (sm:grid-cols-2, action row col-span penuh).
- Halaman index menampilkan kartu statistik tepat di bawah header memakai `.app-card-grid` (2 kolom mobile, 6 kolom desktop) sebagai standar ringkasan status.
- Khusus halaman Services, gunakan modifier `.app-card-grid--services` untuk grid 4 kolom di desktop.
- Inquiry detail view menggunakan `$canManageInquiry` berbasis policy `update` untuk mengunci aksi follow-up & komunikasi.
- Policy Inquiry `update`: hanya creator atau user `assigned_to` yang dapat mengubah inquiry.
- Reminder follow-up di Inquiry detail dapat dibuat oleh role Reservation/Director/Manager atau creator (policy update).
- Inquiry follow-up menyimpan `created_by` (user pembuat reminder) dan ditampilkan di list reminder.
- Saat menandai follow-up `Mark Done`, user wajib mengisi alasan (`done_reason`) melalui modal.
- Modal follow-up ditaruh di luar tabel agar layout tabel tidak terpengaruh.
- Modal follow-up hanya dirender untuk reminder yang belum done.
- Mark Done follow-up hanya boleh oleh creator inquiry atau user `assigned_to`.
- Sistem activity log terpusat memakai tabel `activity_logs` + trait `LogsActivity` (created/updated/deleted).
- Modul Inquiry menambah log manual untuk reminder/communication ke activity log terpusat.
- Model memiliki relasi `activities()` (morphMany) dan dapat ditampilkan dengan `<x-activity-timeline />`.
- Activity Timeline di detail Inquiry ditempatkan tepat di bawah card Inquiry Overview (kolom kiri).
- Activity Timeline menampilkan ringkas: "Action (YYYY-MM-DD HH:MM) by User".
- List Activity Timeline tanpa card/border per item (hanya teks standar agar lebih ringkas).
- Setiap item Activity Timeline diawali tanda "-".
- Reminder status "Done" menampilkan ikon view reason (tooltip via title) jika `done_reason` tersedia.
- Ikon view reason pada reminder membuka modal berisi reason saat diklik.
- Modal view reason juga menampilkan reminder note (jika ada).
- Reminder note & done reason dirender sebagai HTML agar style tersimpan tampil di modal (gunakan output HTML tersanitasi jika perlu).
- PDF Itinerary tidak menampilkan baris "Travel from ..." di header day.
- PDF Itinerary: setiap day dimulai di halaman baru (kecuali day pertama).
- PDF Itinerary: kolom "Thumbnail" diganti menjadi "Image".
- Itineraries index menampilkan nama creator tepat di bawah judul itinerary.
- Itineraries index: kolom Duration menampilkan destination (di bawah durasi).
- Itineraries index: kolom Attractions dihapus.
- Itineraries create/edit: input Room Qty di start point & end point dihilangkan.
- Itineraries create/edit: card section berwarna per tipe (Start Point, Attraction, Activity, F&B, End Point).
- Start/End Point card dinamis mengikuti tipe (airport/accommodation) dengan warna khusus, dan base color start/end disamakan.
- Itineraries create/edit: Inquiry Detail menampilkan Reminder Note dan Done Reason jika tersedia.
- Itineraries create/edit: card "Itinerary Route Preview" dibuat sticky (top-6) saat scroll.
- Itineraries create/edit: Destination + Duration Days + Duration Nights ditampilkan dalam satu baris.
- Customer delete diganti Deactivate/Activate (soft delete) dan tombol Delete dihapus dari UI.
- Statistik di halaman index gunakan `app-card` sebagai kontainer dan `app-card-grid` untuk kartu ringkasan.
- Statistik Customers tidak menampilkan judul section (langsung tampilkan poin/angka di dalam app-card).
- Statistik index mengikuti pola Inquiries: ringkasan di atas (app-card-grid), filter di kiri, table di kanan.
- Icon statistik wajib memakai Font Awesome via `<x-index-stats>` dan mengikuti mapping berdasarkan `key` (mis. vendors, accommodations, pending, approved).
- Summary card di Customers menggunakan ikon (bukan inisial huruf) agar konsisten dengan UI modern.
- Summary card Inquiries menggunakan ikon status (bukan inisial huruf).
- Tombol aksi di tabel (Detail/View/Edit) memakai ikon Font Awesome (eye/pen) dengan `title` + `sr-only` text untuk aksesibilitas.
- Inquiry notes di preview Itineraries dirender sebagai HTML tersanitasi agar typography mengikuti konten.
- Itinerary status diset otomatis ke `draft` saat create; field status tidak ditampilkan di form create/edit.
- Setelah itinerary dibuat/diupdate, inquiry terkait otomatis berubah dari `draft` ke `processed` (jika belum final).
- Halaman index modul (termasuk Airports) mengikuti layout standar: header + action, filter card di kiri, table card di kanan, mobile card list di bawah.
- Standar index: `page_title/page_subtitle/page_actions` di atas, filter form `sm:grid-cols-2`, table di `app-card` (desktop), dan `md:hidden` card list untuk mobile.
- Filter standar index: gunakan `search` (nama/email), `role` (jika relevan), dan `per_page` (10/25/50/100) + `withQueryString()` agar pagination mempertahankan filter.
- Destinations index memakai filter `per_page` (10/25/50/100) dan dikirim ke controller untuk pagination.
- Destinations Linked Data tidak menampilkan F&B terpisah karena sudah diwakili oleh Vendor.
- Destinations detail: kartu "Services Availability" ditampilkan tepat di bawah header (menggantikan "Linked Modules").
- Services Availability di Destinations detail mengikuti gaya statistik Inquiries (menggunakan `<x-index-stats>`).
- Semua modul master data + transaksi utama menggunakan soft delete (`deleted_at`) dengan toggle Deactivate/Activate.
- Tombol Deactivate wajib pakai `btn-muted-sm`, tombol Activate pakai `btn-primary-sm`.
- `btn-muted-sm` mengikuti ukuran/height tombol action lain (diselaraskan di CSS button system).
- Status Active/Inactive ditampilkan di index (desktop + mobile).
- Filter/form dropdown hanya memuat data aktif (exclude soft-deleted).
- Vendors: gunakan soft delete (`deleted_at`) dan nonaktifkan vendor lewat toggle `is_active` (Deactivate/Activate).
- Vendors menyimpan data perusahaan/provider untuk layanan Activities, Food & Beverage, dan Transport.
- Vendor delete diblokir jika masih dipakai oleh Activities, Food & Beverage, atau Transports; tampilkan pesan dan arahkan untuk Deactivate.
- Setiap halaman index menampilkan statistik tepat di bawah header memakai `<x-index-stats :cards="$statsCards ?? []" />` (diisi via `IndexStatsComposer`).
- Itinerary detail day filter wajib memakai `day-filter-btn` + `btn-outline-sm`, dan toggle JS ke `btn-primary-sm` saat aktif.
- Itineraries index memiliki filter Title/Destination/Duration dan memakai `per_page` standar (10/25/50/100).
- Itineraries index: filter Destination memakai `destination_id` dari modul Destinations (label menampilkan `name` saja).
- Jika `destination_id` belum ada di database (migrasi belum dijalankan), filter otomatis fallback ke kolom `destination` agar tidak error.
- Semua form submit menampilkan spinner dan disable tombol submit untuk mencegah double submit (bisa di-skip dengan `data-skip-spinner="1"` pada button atau `data-disable-submit-lock="1"` pada form).
- Standar filter index lintas modul mengacu ke pola `Activities` (baseline resmi):
  1. Wrapper halaman: `data-<module>-index` + `data-page-spinner="off"`.
  2. Form filter: `data-<module>-index-form` + `data-disable-submit-lock="1"` + `data-page-spinner="off"`.
  3. Input filter yang memicu refresh: `data-<module>-filter-input`.
  4. Tombol reset filter: `data-<module>-filter-reset` (link, bukan submit).
  5. Area hasil: `data-<module>-index-results-wrap`.
  6. Hasil list/table dipisah ke partial `resources/views/modules/<module>/partials/_index-results.blade.php`.
  7. Controller index wajib dukung AJAX fragment (`html` + `url`) via method `wantsAjaxFragment()` dan header `X-<Module>-Ajax`.
  8. Pagination di partial wajib mempertahankan query filter (`withQueryString()` di controller).
- Implementasi standar filter ini sudah diterapkan untuk `Activities`, `Hotels`, dan `Tourist Attractions`; modul lain yang belum sesuai wajib mengikuti pola yang sama saat refactor berikutnya.

## 4. Modul Aktif (Seeder)
Modul yang terdaftar dan dikontrol via tabel `modules` (lihat `ModuleSeeder`):
- Module Management (`service_manager`)
- Customer Management (`customer_management`)
- Inquiries (`inquiries`)
- Quotations (`quotations`)
- Bookings (`bookings`)
- Invoices (`invoices`)
- User Manager (`user_manager`)
- Role Manager (`role_manager`)
- Destinations (`destinations`)
- Vendor Management (`vendor_management`)
- Activities (`activities`)
- Food & Beverage (`food_beverages`)
- Accommodations (`accommodations`)
- Hotels (`hotels`)
- Airports (`airports`)
- Transports (`transports`)
- Itineraries (`itineraries`)
- Tourist Attractions (`tourist_attractions`)
- Currencies (`currencies`)

Fitur non-modul (route-based):
- Dashboards: Super Admin, Administrator, Sales, Accountant, Reservation, Director.
- Access Matrix (Super Admin).
- Company Settings (Director).

## 5. Role & Permission
Roles utama (lihat `RoleSeeder`):
- Super Admin
- Administrator
- Director
- Manager
- Reservation
- Accountant
- Marketing
- Editor

### hak akses dan aktivitas utama
1. Super Admin
- Kontrol penuh terhadap seluruh sistem.
Yang bisa dilakukan:
#### a. System Management
- Mengelola semua user
- Mengatur role & permission
- Mengatur department
- Konfigurasi sistem
#### b. Master Data
- Services
- Packages
- Transport
- Accommodation
- Vendors
#### c. Financial Control
- Semua invoice
- Semua payment
- Semua expense
#### d. System Control
- Activity logs
- System settings
- Backup
- Integration API
#### e. Reporting
- Semua laporan bisnis
- Export data

2. Administrator
- Mengelola operasional sistem (tanpa akses penuh seperti super admin).
Yang bisa dilakukan:
#### a. User Management
- Membuat user
- Mengedit user
- Assign role
#### b. Master Data
- Paket tour
- Services
- Vendors
- Transport
- Accommodation
#### c. Operational Control
- Monitor inquiry
- Monitor booking
- Monitor quotation
#### d. Reporting
- Sales report
- Booking report
- Customer report

3. Director
- Level eksekutif, fokus pada monitoring bisnis dan keputusan.
Yang bisa dilakukan:
#### a. Dashboard
- Revenue overview
- Sales performance
- Booking statistics
- Profit margin
#### b. Reports
- Sales report
- Financial report
- Vendor performance
- Customer acquisition
#### c. Approval
- Approval discount besar
- Approval special pricing
#### d. View Only
- Inquiry
- Quotation
- Booking
- Invoice
Tidak melakukan operasional harian.

4. Manager
- Mengelola tim sales dan operasional.
Yang bisa dilakukan:
#### a. Sales Management
- Assign inquiry ke reservation
- Approve quotation
- Approve discount
#### b. Operational Monitoring
- Monitor booking progress
- Monitor follow-up inquiry
#### c. Performance
- Sales performance per staff
- Conversion rate
#### d. Reports
- Sales report
- Booking report

5. Reservation
- Role utama untuk sales dan booking handling.
Yang bisa dilakukan:
#### a. Customer
- Create customer
- Edit customer
#### b. Inquiry
- Create inquiry
- Update inquiry
- Follow-up inquiry
#### c. Quotation
- Create quotation
- Edit quotation
- Send quotation
#### d. Booking
- Convert quotation → booking
- Input participant
- Upload document
#### e. Communication
- Send quotation via email
- Follow-up customer

6. Accountant
- Mengelola akuntansi dan laporan keuangan.
Yang bisa dilakukan:
#### a. Invoice
- Generate invoice
- Edit invoice
- Manage invoice items
#### b. Payments
- Record payment
- Verify payment
- Payment reconciliation
#### c. Finance Report
- Income statement
- Accounts receivable
- Cashflow report
#### d. Export
- Excel
- Accounting export

7. Marketing
- Fokus pada lead dan customer acquisition.
Yang bisa dilakukan:
#### a. Lead Management
- Input leads
- Manage customer database
- Track lead source
#### b. Campaign
- Marketing campaign
- Email campaign
- Promo package
#### c. Analytics
- Lead conversion
- Campaign performance
- Customer segmentation
#### d. Content
- Package promotion
- Landing page

8. Finance
- Fokus pada cashflow dan expense management.
Yang bisa dilakukan:
#### a. Payments
- Monitor payments
- Payment verification
#### b. Expenses
- Input expense
- Allocate expense to booking
#### c. Financial Monitoring
- Profit per booking
- Cost tracking
#### d. Reports
- Financial reports
- Expense reports

9. Editor
- Mengelola konten dan katalog layanan.
- Yang bisa dilakukan:
#### a. Tour Packages
- Create package
- Edit itinerary
- Update inclusions
#### b. Service Catalog
- Update services
- Update pricing
- Update descriptions
#### c. Media
- Upload images
- Upload documents
#### d. Content
- Website content
- Tour description

Middleware:
- auth
- role:Administrator|Manager|...
- permission:module.*
- module:module_name
- block.superadmin.target:user (proteksi target Super Admin)

Catatan:
- Discount/Promo dan Approval: Manager & Director.
- Booking: Reservation & Manager.
- Service management: Administrator | Reservation | Manager | Editor.

## 6. Struktur & Relasi Data (Ringkas)
- User hasMany Customer (created_by)
- User hasMany Inquiry (assigned_to)
- Customer hasMany Inquiry
- Inquiry hasOne Quotation
- Quotation hasMany QuotationItem, hasOne Booking
- QuotationItem belongsTo Service
- Service belongsTo Vendor
- Transport belongsTo Vendor (provider)
- Accommodation hasOne Hotel
- Hotel hasMany HotelRoom, HotelImage, HotelType, HotelPrice, HotelPromo, HotelPackage, ExtraBed
- RoomView hasMany HotelRoom
- Hotels support `facility_traditional` and `facility_simplified` fields for multi-language facility notes.
- Hotels support `additional_info_traditional` and `additional_info_simplified` fields for multi-language additional info.
- Hotels support `cancellation_policy_traditional` and `cancellation_policy_simplified` fields for multi-language cancellation policy.
- Hotel rooms support `include`, `include_traditional`, and `include_simplified` fields for multi-language included items.
- InquiryFollowUp & InquiryCommunication terkait Inquiry
- Itinerary memiliki item per tipe (Activity, FoodBeverage, TransportUnit)
- Itinerary memiliki titik per hari (ItineraryDayPoint)
- Destinations menjadi master lokasi untuk modul berbasis lokasi

## 7. Konvensi UI & Layout
Gunakan layout master dan section berikut:
- page_title, page_subtitle, page_actions
- Starter template: resources/views/templates/module-page.blade.php

Catatan:
- Breadcrumbs otomatis dari route name.
- Header bisa disembunyikan dengan page_header_hidden.
- Semua tabel harus berada di dalam card `app-card` untuk konsistensi UI.
- Tabel wajib memakai kelas `app-table` agar padding/typography/hover konsisten.
- Semua halaman index mengikuti layout standar Customers:
  - Grid 12 kolom (`grid grid-cols-1 gap-6 xl:grid-cols-12`).
  - Kiri: `aside` filter dalam `app-card` (judul + deskripsi singkat).
  - Kanan: konten utama (alert, mobile cards, table, pagination).
  - Table wrapper: `hidden md:block app-card overflow-hidden` + `overflow-x-auto`.
- Semua tombol wajib mengikuti standar button modern: gunakan kelas `btn-primary`,
  `btn-secondary`, `btn-primary-sm`, `btn-secondary-sm`, `btn-ghost`,
  `btn-ghost-sm`, `btn-outline`, `btn-outline-sm` atau tombol ber-style rounded
  untuk efek hover dinamis yang konsisten.
- Semua input (kecuali `textarea`) wajib mengikuti standar global yang sama: tinggi control `42px`, ukuran font `14px`, border radius `0.5rem`, dan state focus seragam; terapkan lewat `app-content` + class `app-input`.
- Semua label field wajib konsisten (`min-height` label standar, font size 14px, line-height seragam) agar alignment antar field stabil.
- Template form wajib memakai `app-input` pada `input/select` dan tidak memakai style custom per-field untuk tinggi/padding/font (kecuali kebutuhan fungsional seperti `uppercase`).
- Untuk field dengan icon/label di dalam input (suffix/prefix), gunakan wrapper `input-with-right-affix` dan elemen `input-right-affix` agar posisi selalu vertical-center dan rata kanan.
- Khusus input `google_maps_url`, lebar wajib 100% (override global min-width 50%).
- Field `Google Maps URL` ditampilkan full-width; default layout vertikal dengan tombol Auto Fill di baris bawah. Khusus halaman Destinations, tombol Auto Fill ditampilkan inline di kanan input.
- Halaman detail Quotation mengikuti pola UX standar:
  1. Header action ringkas (`Edit` bila diizinkan, `PDF`, `Back`) memakai kelas tombol standar.
  2. Konten utama `module-grid-9-3`: kiri untuk overview + pricing + items, kanan untuk context (inquiry/itinerary), validation flow, comment, audit.
  3. Gunakan `app-card` konsisten di semua section (hindari campuran card style lama).
  4. Wajib ada versi mobile-friendly untuk item list (card stack) selain tabel desktop.
  5. Financial summary menonjolkan `Final Amount` sebagai titik keputusan utama user.
- Standar field untuk modul yang memakai Google Map URL wajib mencakup 7 data: `address`, `city`, `province`, `country`, `latitude`, `longitude`, `destination_id`.
- Urutan blok wajib pada halaman Create/Edit (template standar map):
  1. `Location on Map (open map)`
  2. `Map URL (Google Maps)` (input + tombol Auto Fill)
  3. `Latitude` dan `Longitude` (auto fill dari Map URL)
  4. `Address` (auto fill dari Map URL)
  5. `City` dan `Province` (auto fill dari Map URL)
  6. `Country` (auto fill dari Map URL)
  7. `Destination` (sinkronisasi dengan hasil lokasi/province)
- Implementasi awal standar ini dimulai dari modul Hotels dan menjadi patokan untuk modul lain secara bertahap.
- Wajib gunakan partial reusable `resources/views/components/map-standard-section.blade.php` untuk semua modul yang memakai Google Maps URL agar tidak terjadi drift layout/field.
- Cara pakai standar:
  1. Siapkan container form dengan `data-location-autofill` + `data-location-resolve-url`.
  2. Include partial map standar dan kirim `mapPartial`, `mapValue`, `latitudeValue`, `longitudeValue`, `addressValue`, `cityValue`, `provinceValue`, `countryValue`, `destinationValue`, `destinations`.
  3. Simpan field tambahan non-standar (misalnya `location` hidden, `timezone`) di luar partial, setelah blok map standar.
  Palet warna tombol standar (berdasarkan referensi UI):
  - Primary: #1ea7a0
  - Secondary: #ffffff (border #e5e7eb)
  - Accent/Outline/Ghost: #1ea7a0
  - Warning: #fec601
  - Danger: #ea7317
  Status badge: pill/rounded-full, lowercase title-case, soft background.

## 8. Aturan Pengembangan (AI Directive)
Setiap perubahan harus:
- Menjaga alur utama sales process.
- Menghindari duplikasi data yang tidak perlu.
- Menjaga performa dan maintainability.
- Konsisten dengan rencana jangka panjang:
  - Multi-tenant
  - Payment gateway
  - Email/WA automation
  - Reporting/BI

Catatan modul:
- Akses modul dikontrol oleh middleware `module:*`.
- Konfigurasi fail-open ada di `config/modules.php`.

## 9. Prioritas Kritis (Roadmap)
Fokus utama pengembangan:
- Approval workflow (Quotation)
- Margin & profit calculation
- Structured itinerary engine
- Expense -> profit linking
- Audit trail system
- Participant management
- Auto reminder engine

## 10. Standar Implementasi
Saat menambah fitur:
- Gunakan validasi yang jelas di controller/request.
- Pakai eager loading untuk list besar.
- Gunakan pagination.
- Pastikan seeding dan migrations rapi.
- Pastikan permission/role mapping diperbarui.

## 11. Status Final (Wajib)
Untuk modul: Inquiries, Itineraries, Quotations, Bookings, Invoices:
- Status standar: `draft`, `processed`, `pending`, `approved`, `rejected`, `final`.
- Jika status = `final`, data hanya boleh di-view (tanpa edit/hapus/aksi perubahan).

## 12. QA Cepat (Wajib Setelah Perubahan)
Setiap kali selesai melakukan penyesuaian pada project ini, wajib lakukan QA cepat:
- Cek visual dasar halaman terkait (layout, spacing, dan konsistensi card).
- Cek aksi utama (create/edit/list/show) pada halaman yang diubah.
- Cek kondisi kosong/error state pada halaman yang diubah jika relevan.
- Pastikan tidak ada error di console (JS) jika halaman menggunakan interaksi.
- Catat ringkas hasil QA di respons pekerjaan.

## 13. Dokumentasi Lanjutan
Untuk detail tambahan, rujuk:
- ANALYSIS_REPORT.md
- QUICK_SUMMARY.md
- VOYEX_CRM_AI_GUIDELINE.md
- VOYEX_CRM_SYSTEM_ROADMAP.md
- LAYOUT_GUIDE.md
- modul.md
