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

## 5. Reservation Dashboard Caching (2026-04-17)

Masalah:
- Dashboard Reservation mengeluarkan beberapa query agregat berat yang dieksekusi setiap kali halaman dimuat pada pengguna aktif.

Perbaikan:
- Menambahkan caching singkat (TTL 120s) untuk query berat pada `Reservation` dashboard: status counts, monthly counts, weekly trend, top customers, dan booking-by-staff.
- Implementasi menggunakan `Cache::remember()` di `app/Http/Controllers/Reservation/DashboardController.php` dengan kunci namespaced `reservation:*`.

Dampak:
- Meningkatkan responsivitas dashboard pada traffic tinggi dan mengurangi beban database sementara masih menampilkan data hampir real-time.

Referensi kode:
- `app/Http/Controllers/Reservation/DashboardController.php`

## 6. Reservation Dashboard KPI & Status Alignment (2026-04-17)

Masalah:
- Dashboard Reservation masih memakai status booking legacy (`confirmed`) pada beberapa query KPI/list.
- KPI card `Quotations Ready to Book` dan `Upcoming Trips` membaca key yang belum dipopulasi dari controller.
- Label panel Upcoming Trips (`Next 30 Days`) tidak sesuai dengan scope query.

Perbaikan:
- Menyelaraskan query booking ke lifecycle status aktif (`processed`, `approved`, `final`) dan menghapus ketergantungan status legacy.
- Menambahkan populasi KPI `kpis.ready_to_book` dan `kpis.upcoming_trips` di controller.
- Membatasi query upcoming trip ke rentang 30 hari agar konsisten dengan label UI.
- Menyaring ready-to-book quotation ke quotation `approved` yang belum punya booking (`whereDoesntHave('booking')`).
- Merapikan payload controller dengan menghapus data `statusSummary` yang tidak terpakai.

Dampak:
- Angka KPI dan daftar operasional dashboard lebih akurat terhadap rule status saat ini.
- Tampilan dashboard tidak lagi menampilkan nilai nol semu akibat key KPI yang tidak sinkron.
- Konsistensi label vs perilaku query meningkat untuk operasional Reservation.

## 7. Test Database Safety Guard (2026-04-17)

Masalah:
- Eksekusi `php artisan test` berisiko mengosongkan DB utama jika `RefreshDatabase` berjalan tanpa database testing terisolasi.

Perbaikan:
- Memaksa konfigurasi DB testing di `phpunit.xml` ke SQLite in-memory.
- Menambahkan guard runtime di `tests/CreatesApplication.php`:
  - jika env `testing` tetapi DB bukan SQLite aman dan nama DB tidak mengandung `test`, test langsung dibatalkan dengan exception.
- Menambahkan `.env.testing` default aman (lokal) dan memasukkan `.env.testing` ke `.gitignore`.

Dampak:
- Mencegah test suite menjalankan migrasi destructive pada database kerja utama.
- Menjadikan kegagalan konfigurasi test terlihat eksplisit sejak awal proses bootstrap test.

## 8. DB-Mutating Test Removal (2026-04-17)

Masalah:
- User membutuhkan jaminan tidak ada lagi test project yang berpotensi mengosongkan/memodifikasi database kerja.

Perbaikan:
- Menghapus seluruh test yang memakai `RefreshDatabase` atau `DatabaseTransactions`, termasuk test yang mewarisi `ModuleSmokeTestCase`.
- Menghapus `tests/Feature/Modules/ModuleSmokeTestCase.php` agar tidak ada inheritance path yang otomatis membuka transaksi DB pada test lain.
- Menyisakan hanya test placeholder non-DB (`Feature/ExampleTest`, `Unit/ExampleTest`).

Dampak:
- Tidak ada lagi file test aktif di project yang melakukan reset DB, migrate test DB, atau transaksi DB otomatis.
- Risiko kehilangan data akibat eksekusi test project turun secara drastis.

## 9. Itinerary Creator-Only Edit Guard (2026-04-17)

Masalah:
- Akses edit Itinerary sebelumnya hanya berbasis permission `module.itineraries.update`, sehingga user non-creator yang punya permission masih bisa mengedit data.

Perbaikan:
- Memperketat `ItineraryPolicy::update` menjadi kombinasi:
  - permission check (`module.itineraries.update`), dan
  - ownership check (`created_by` harus sama dengan user login).

Dampak:
- Edit itinerary konsisten dengan owner-based mutation rule.
- UI action edit yang memakai `@can('update', $itinerary)` otomatis ikut terkunci untuk non-creator.

## 10. Quotation Creator-Only Mutation Guard (2026-04-17)

Masalah:
- Akses mutasi Quotation (`update/delete`) sebelumnya hanya berbasis permission module, sehingga user non-creator dengan permission yang sama masih bisa mengubah data quotation.

Perbaikan:
- Memperketat `QuotationPolicy::update` dan `QuotationPolicy::delete` menjadi kombinasi:
  - permission check (`module.quotations.update/delete`), dan
  - ownership check (`created_by` harus sama dengan user login).
- Menjaga action workflow approval (`approve/reject/set pending/set final`) tetap permission-first sesuai desain proses approval.

Dampak:
- Mutasi data quotation kini konsisten dengan owner-based mutation standard.
- UI action edit/delete yang bergantung pada policy otomatis sinkron ke rule creator-only.

## 11. Booking Creator-Only Mutation Guard + Invoice Read-Only Baseline (2026-04-17)

Masalah:
- Akses mutasi Booking (`update/delete`) sebelumnya masih permission-only.
- Perlu kejelasan baseline modul Invoice agar tidak melebar ke mutasi tanpa desain akses.

Perbaikan:
- Memperketat `BookingPolicy::update` dan `BookingPolicy::delete` menjadi kombinasi:
  - permission check (`module.bookings.update/delete`), dan
  - ownership check (`created_by` harus sama dengan user login).
- Menetapkan baseline Invoice tetap read-only pada alur Finance (`index/show`) dengan dokumentasi governance eksplisit.

Dampak:
- Mutasi booking menjadi konsisten dengan creator-only standard lintas modul owner-centric.
- Mencegah drift arsitektur pada modul Invoice tanpa kontrol policy yang terdefinisi.

## 12. Role Form System Tools Permission Grouping (2026-04-17)

Masalah:
- Permission `View Service Map` dan `View Access Matrix` tersedia, tetapi kurang terlihat di Role edit karena tercampur dalam daftar generic non-module permissions.

Perbaikan:
- Menambahkan grouping khusus `System Tools Permissions` pada form role create/edit.
- Memisahkan permission berikut dari daftar generic agar tampil eksplisit:
  - `services.map.view`
  - `superadmin.access_matrix.view`
- Tetap mempertahankan label ramah:
  - `View Service Map`
  - `View Access Matrix`

Dampak:
- Pengaturan akses Service Map dan Access Matrix pada Role & Permission edit menjadi lebih jelas, cepat ditemukan, dan minim salah konfigurasi.


## Referensi Kode

- `app/Traits/LogsActivity.php`
- `resources/views/components/activity-timeline.blade.php`
- `app/Http/Controllers/DashboardRedirectController.php`
- `app/Http/Middleware/EnsureModulePermission.php`
- `app/Policies/*Policy.php`
- `app/Http/Controllers/Reservation/DashboardController.php`
- `resources/views/reservation/dashboard.blade.php`
- `tests/CreatesApplication.php`
- `phpunit.xml`
- `tests/Feature/ExampleTest.php`
- `tests/Unit/ExampleTest.php`
- `app/Policies/ItineraryPolicy.php`
- `app/Policies/QuotationPolicy.php`
- `app/Policies/BookingPolicy.php`
- `app/Http/Controllers/Finance/InvoiceController.php`
- `app/Http/Controllers/Admin/RoleController.php`
- `resources/views/modules/roles/_form.blade.php`

## Catatan Governance

Status detail dan perubahan harian resmi tetap dicatat di:
- `VOYEX_CRM_SYSTEM_ROADMAP.md` bagian `CHANGELOG (LATEST)`
