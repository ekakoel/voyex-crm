# Technical Fix Notes

Last Updated: 2026-04-20

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

## 13. Baseline Seeder Single Entry Consolidation (2026-04-17)

Masalah:
- Seeder project tersebar dan dijalankan terpisah, sehingga rawan lupa urutan dan membingungkan saat deploy environment baru.

Perbaikan:
- Menambahkan `ProjectBaselineSeeder` sebagai entrypoint tunggal baseline seeding project.
- Mengarahkan `DatabaseSeeder` untuk memanggil `ProjectBaselineSeeder`.
- Menjaga `PermissionBaselineSeeder` untuk skenario sinkronisasi permission-only.
- Menambahkan shortcut deploy DB di `composer.json`:
  - `db:baseline`
  - `db:deploy-safe`
- Memperbarui README dengan command baseline deploy yang direkomendasikan.

Dampak:
- Proses seed database lebih konsisten, ringkas, dan minim human-error.
- Deploy lintas environment menjadi lebih mudah diulang dengan command yang sama.

## 14. Inquiry Index Itinerary Status Label (2026-04-17)

Masalah:
- Pada index Inquiry, kolom itinerary hanya menampilkan nama itinerary tanpa status lifecycle, sehingga user perlu membuka detail untuk verifikasi status.

Perbaikan:
- Menambahkan field `status` pada eager-load relation `itineraries` di `InquiryController@index`.
- Memperbarui tampilan index Inquiry (desktop + mobile) menjadi:
  - `Nama Itinerary (Status)`.

Dampak:
- Informasi status itinerary terlihat langsung dari list Inquiry.
- Mengurangi klik tambahan untuk pengecekan progres itinerary.

## 15. Inquiry Assignee Privilege Alignment (2026-04-17)

Masalah:
- Pada modul Inquiry, policy update sebelumnya hanya mengizinkan creator. User yang di-assign inquiry tidak bisa edit inquiry atau menambah communication history meskipun bertanggung jawab sebagai assignee.

Perbaikan:
- Menambahkan helper ownership `isAssignedTo()` pada model `Inquiry`.
- Memperbarui `InquiryPolicy::update` menjadi:
  - wajib punya permission `module.inquiries.update`, dan
  - ownership `creator OR assigned user`.
- Menyelaraskan guard follow-up (`canManageFollowUp`, `canMarkFollowUpDone`) agar mengikuti policy update yang sama.

Dampak:
- Assignee inquiry mendapatkan hak mutasi setara creator untuk flow inquiry (edit, communication history, follow-up), selama memiliki permission update inquiry.
- Akses UI `@can('update', $inquiry)` otomatis sinkron dengan rule creator/assignee.

## 16. Itinerary PDF Item Description Removal (2026-04-17)

Masalah:
- Pada PDF itinerary, kolom `Item` menampilkan blok deskripsi tambahan yang membuat baris item terlalu panjang dan mengurangi keterbacaan ringkasan jadwal.

Perbaikan:
- Menghapus render `item['description']` pada kolom `Item` di template `resources/views/pdf/itinerary.blade.php`.
- Nama item, marker `Main Experience`, dan blok include/exclude khusus activity tetap dipertahankan.

Dampak:
- Tampilan PDF itinerary lebih ringkas dan fokus pada informasi inti per item.
- Mengurangi kepadatan konten pada tabel schedule harian.

## 17. Quotation-With-Itinerary PDF Item Description Removal (2026-04-20)

Masalah:
- Template PDF quotation yang memuat itinerary masih menampilkan blok `description` pada kolom `Item`, sehingga tidak konsisten dengan PDF itinerary yang sudah diringkas.

Perbaikan:
- Menghapus render `item['description']` pada kolom `Item` di `resources/views/pdf/quotation_with_itinerary.blade.php`.
- Konten penting lain tetap dipertahankan (nama item, marker main experience, menu highlights F&B, includes/excludes activity).

Dampak:
- Output PDF quotation dengan itinerary lebih konsisten dan ringkas.
- Keterbacaan tabel schedule meningkat karena informasi non-esensial pada kolom item dihilangkan.

## 18. Quotation Validation Button Spinner Consistency (2026-04-20)

Masalah:
- Pada halaman quotation validation, tombol `Validate` tidak selalu menampilkan spinner saat diklik karena state loading hanya ditoggle ke elemen pertama yang ditemukan, sementara aksi item tersedia pada dua layout (mobile dan desktop).

Perbaikan:
- Menambahkan helper `setItemButtonLoading(itemId, isLoading)` di `resources/views/modules/quotations/validate.blade.php`.
- Loading state sekarang diterapkan ke semua tombol `data-save-item` dengan `itemId` yang sama:
  - `Validate` label disembunyikan,
  - spinner ditampilkan,
  - tombol didisable sampai request selesai.
- Menambahkan loading-state submit untuk action utama:
  - `Save Progress`,
  - `Validate Quotation`,
  dengan toggle spinner + label pada saat form submit.

Dampak:
- Feedback loading menjadi konsisten saat klik `Validate`.
- Mengurangi risiko double-submit pada aksi validasi item.

## 19. Itinerary Start/End Point Optional Input (2026-04-20)

Masalah:
- Form create/edit itinerary memaksa `Day N End Point` terisi (client-side) dan default Start/End point sering dipaksa otomatis, sehingga user tidak bisa menyimpan hari dengan titik start/end kosong.

Perbaikan:
- Menghapus rule client-side yang memblokir submit saat end point kosong.
- Menambahkan opsi `Not set` pada selector `Day N Start Point` dan `Day N End Point`.
- Menghapus default paksa start/end point pada inisialisasi dan saat clone day section.
- Memperbarui validasi controller untuk menerima empty-string pada `daily_start_point_types.*` dan `daily_end_point_types.*`.
- Memperbarui normalisasi day points agar tipe kosong disimpan sebagai `null` dan tidak memicu validasi item/room wajib.

Dampak:
- User dapat mengosongkan start/end point saat create/update itinerary sesuai kebutuhan operasional.
- Data day point tersimpan lebih fleksibel tanpa memaksa struktur titik yang belum ditentukan.

## 20. Itinerary Create/Edit Error-Performance Sweep (2026-04-20)

Masalah:
- Terdapat typo atribut HTML pada opsi hotel start point (`data-longitude`) di form itinerary, berpotensi mengganggu pembacaan dataset oleh skrip peta/filter.
- Proses normalisasi day points menjalankan query pemetaan room->hotel dua kali berturut-turut.

Perbaikan:
- Memperbaiki atribut menjadi valid: `data-longitude="{{ $hotel->longitude ?? '' }}"`.
- Menghapus query duplikat dan mempertahankan satu kali `HotelRoom::query()->pluck('hotels_id', 'id')`.
- Menjalankan validasi kompilasi Blade via `php artisan view:cache`.

Dampak:
- Menurunkan risiko error front-end terkait metadata lokasi hotel.
- Mengurangi beban query yang tidak perlu saat submit create/update itinerary.
- Menambah confidence bahwa template create/edit itinerary dapat dikompilasi tanpa error.

## 21. Island Transfer Index UI/UX Standardization (2026-04-20)

Masalah:
- Halaman index `Island Transfer` belum mengikuti pola UI/UX standard module list di project (layout filter, komposisi grid, mobile card, dan konsistensi komponen status/action).

Perbaikan:
- Merapikan layout index menjadi pola `module-grid-3-9`:
  - panel filter di sisi kiri,
  - hasil list di sisi kanan.
- Menambahkan `x-index-stats` cards untuk ringkasan cepat (Total, Active, Inactive, Fastboat).
- Menyamakan panel filter dengan hook standar (`data-service-filter-*`) dan kontrol reset.
- Menyamakan tabel desktop:
  - `app-table`,
  - status via `x-status-badge`,
  - action buttons konsisten (`btn-outline-sm`, `btn-secondary-sm`, `btn-muted-sm/btn-primary-sm`).
- Menambahkan versi mobile card agar pengalaman responsif setara modul service lain.

Dampak:
- UI halaman `Island Transfer` kini konsisten secara visual dan alur interaksi dengan modul service lain.
- Posisi filter, hasil data, status, dan aksi menjadi lebih mudah dipahami user lintas modul.

## 22. Island Transfer Create/Edit/Show UI/UX Standardization (2026-04-20)

Masalah:
- Halaman `create`, `edit`, dan `show` modul `Island Transfer` belum sepenuhnya mengikuti komposisi layout modul service lain, terutama pada sidebar, detail card grouping, dan quick actions.

Perbaikan:
- Menyelaraskan `create` dan `edit` ke pola:
  - `module-page--island-transfers`,
  - `module-grid-8-4`,
  - `module-form-wrap`.
- Menambahkan sidebar pendukung pada `edit`:
  - `Vendor Information`,
  - `Audit Info`.
- Merapikan halaman `show` menjadi detail layout standar:
  - main content berupa card per-section (`General`, `Route Details`, `Route GeoJSON`, `Notes`),
  - sidebar `Quick Actions` + `Vendor Information` + `Audit Info`.
- Menyamakan styling input textarea pada form (`app-input`) agar visual form konsisten.

Dampak:
- Pengalaman create/edit/show Island Transfer kini konsisten satu paket dengan modul service lain.
- Navigasi aksi cepat dan informasi audit/vendor lebih mudah diakses user.

## 23. Island Transfer UI Copywriting Language Standard (EN) (2026-04-20)

Masalah:
- Copywriting pada modul `Island Transfer` masih campuran EN/ID dan ada istilah action yang tidak seragam (`Detail`, `View Detail`, dll).

Perbaikan:
- Menetapkan bahasa UI modul `Island Transfer` ke **full English**.
- Menyamakan label action menjadi konsisten:
  - `View Details`,
  - `Back`,
  - `Deactivate` / `Activate`.
- Menyamakan istilah tipe layanan:
  - `Fast Boat` (bukan campuran `Fastboat`/`FASTBOAT`).
- Mengubah helper text form ke EN, termasuk petunjuk `Route GeoJSON`.
- Menyamakan flash message controller ke style EN yang konsisten (`Island Transfer ... successfully.`).

Dampak:
- Copywriting modul `Island Transfer` sekarang satu bahasa dan konsisten lintas index/create/edit/show/form.

## 24. Island Transfer i18n Extraction to `ui.modules.island_transfers.*` (2026-04-20)

Masalah:
- Copy modul `Island Transfer` masih tersebar sebagai hardcoded string di Blade dan controller, sehingga rawan drift bahasa saat perubahan berikutnya.

Perbaikan:
- Menambahkan namespace baru `ui.modules.island_transfers` di `lang/en/ui.php`.
- Memindahkan seluruh copy utama modul ke key i18n tersebut:
  - title/subtitle,
  - filter labels,
  - table labels,
  - form labels/helper texts,
  - detail page labels,
  - quick actions + confirmation messages,
  - stats labels dan flash messages.
- Refactor semua view modul (`index/create/edit/show/_form`) + `IslandTransferController` untuk membaca string dari translation key.

Dampak:
- Standar bahasa modul terjaga dari satu sumber (`lang/en/ui.php`).
- Perubahan bahasa ke depan lebih aman dan cepat tanpa menyentuh banyak file view/controller.

## 25. Island Transfer Google Maps URL Coordinate Auto-Fill (2026-04-20)

Masalah:
- User masih harus input manual `departure/arrival latitude-longitude`, sehingga rentan salah ketik saat sumber data sebenarnya sudah ada di URL Google Maps.

Perbaikan:
- Menambahkan field URL terpisah pada form `Island Transfer`:
  - `Departure Google Maps URL`
  - `Arrival Google Maps URL`
- Menambahkan tombol `Auto Fill Coordinates` untuk masing-masing section.
- Menambahkan parser client-side untuk ekstraksi koordinat dari beberapa format URL Google Maps:
  - `?q=lat,lng` / `?query=lat,lng` / `?ll=lat,lng`,
  - pola `@lat,lng`,
  - pola `!3dlat!4dlng`.
- Menambahkan key i18n baru untuk label/helper/error terkait fitur ini.

Dampak:
- Input koordinat jadi lebih cepat dan akurat pada create/edit Island Transfer.
- Mengurangi human error tanpa mengubah skema database.
- UX lebih jelas karena pola istilah aksi dan label tidak berubah-ubah.

## 26. Quotation Island Transfer Enum Alignment + Error Feedback (2026-04-20)

Masalah:
- Setelah penambahan modul Island Transfer, sebagian row quotation gagal disimpan dengan error:
  - `The selected items.*.serviceable_type is invalid`,
  - `The selected items.*.itinerary_item_type is invalid`.
- Pada beberapa kasus, user tidak mendapat konteks error yang cukup jelas saat save gagal.

Perbaikan:
- Menyamakan nilai enum lintas sumber data quotation (form hidden fields, generator item itinerary, dan validator controller):
  - `serviceable_type` memakai class FQCN canonical termasuk `App\Models\IslandTransfer`,
  - `itinerary_item_type` memakai enum canonical termasuk `transfer`.
- Menegaskan bahwa item Island Transfer masuk ke pipeline quotation:
  - create/edit form,
  - validation scope,
  - PDF itinerary dan PDF quotation-with-itinerary.
- Menambahkan dokumentasi eksplisit mapping canonical agar regresi serupa dapat dicegah saat penambahan modul service berikutnya.

Dampak:
- Save quotation tidak lagi gagal karena mismatch enum untuk item Island Transfer.
- Pesan error validasi menjadi lebih mudah ditelusuri karena source-of-truth enum sudah terdokumentasi jelas.

## 27. Quotation Validation Realtime Progress + Performance Optimization (2026-04-20)

Masalah:
- Pada halaman quotation validation, tombol `Validate Quotation` tidak langsung muncul setelah item terakhir di-`Validate`; user harus klik `Save Progress` dulu.
- Aksi `Validate Quotation` terasa lambat karena ada proses sinkronisasi validasi yang dieksekusi berulang.

Akar masalah:
- Progress dihitung dari relation `quotation->items` yang bisa stale setelah save item AJAX karena relation tidak di-refresh secara paksa.
- Service validation menjalankan beberapa pass sinkronisasi/progress yang redundan pada alur `saveItem`, `saveProgress`, `validateSelected`, dan `finalize`.
- Setelah finalisasi, redirect ke `quotations.show` masih memicu sinkronisasi master-rate penuh (`syncValidationRequirementsAndMasterRates`) yang berat untuk quotation ber-item banyak.
- Kalkulasi KPI progress validasi masih mengandalkan collection in-memory, bukan agregasi query DB langsung.

Perbaikan:
- Memaksa refresh relation `items` saat hitung progress (`refreshProgress`) agar hasil progress selalu akurat real-time.
- Menambahkan sinkronisasi requirement per-item (`syncValidationRequirementForItem`) untuk alur `saveItem` agar tidak perlu loop seluruh item quotation setiap klik validate item.
- Mengurangi proses redundan:
  - `saveProgress`, `validateSelected`, dan `finalize` kini memanggil sinkronisasi requirement tanpa refresh progress ganda.
  - `syncValidationRequirementsAndMasterRates` kini hanya refresh progress sekali di akhir pipeline.
- Mengubah perhitungan progress ke query agregat DB (`count`/`exists`) agar tidak perlu load semua `quotation_items` ke memory pada tiap aksi.
- Mengganti sinkronisasi berat di `quotations.show`, `quotations.edit`, dan `approve` dari:
  - `syncValidationRequirementsAndMasterRates`
  menjadi:
  - `syncValidationRequirements` (tanpa sync master-rate lintas item).

Dampak:
- Setelah item terakhir tervalidasi via AJAX, tombol `Validate Quotation` langsung tampil tanpa perlu `Save Progress`.
- Waktu respons validasi item dan finalisasi menjadi lebih ringan pada quotation dengan item banyak.
- Waktu transisi setelah klik `Validate Quotation` (redirect ke halaman detail quotation) turun signifikan karena tidak lagi melakukan master-rate sync penuh yang tidak diperlukan di jalur tersebut.

## 28. Quotation Validation Rate Period Upsert (2026-04-20)

Masalah:
- Saat validator mengubah `contract_rate`, `markup_type`, dan `markup`, sistem selalu menambahkan record rate baru.
- Ini berisiko menumpuk record period untuk service yang sebenarnya masih dalam rentang berlaku aktif.

Perbaikan:
- Menambahkan logika upsert berbasis masa berlaku pada pipeline update rate validation:
  - jika ada record rate aktif (tanggal sekarang berada di antara `start_date` dan `end_date`) maka record tersebut di-`update`,
  - jika tidak ada record aktif atau period sudah lewat, sistem membuat record rate baru.
- Diterapkan pada:
  - `service_rate_histories` untuk service type Activity, Food & Beverage, Island Transfer, Transport Unit, Tourist Attraction, dan Hotel Room,
  - `hotel_prices` untuk rate hotel per room.
- Menambahkan helper khusus:
  - `upsertActiveServiceRateHistory(...)`,
  - `findActiveHotelPrice(...)`.

Dampak:
- Data rate lebih bersih dan sesuai rule period validity.
- Update validation tidak menambah record baru secara tidak perlu saat period aktif masih berlaku.

## 29. Quotation Finalize Lightweight Mode (2026-04-20)

Masalah:
- Aksi `Validate Quotation` masih terasa berat karena tahap finalize menjalankan sinkronisasi requirement seluruh item lagi, padahal item sudah divalidasi/sinkron saat aksi per-item.

Perbaikan:
- Menyederhanakan proses `finalize()` agar:
  - langsung membaca progress aktual dari DB (`getProgress`),
  - memvalidasi syarat completion (`total_required > 0` dan `is_complete`),
  - set status quotation ke `valid` + metadata validator,
  - tanpa loop sinkronisasi ulang ke semua item.

Dampak:
- Waktu respons klik `Validate Quotation` lebih cepat dan stabil.
- Tidak ada perubahan business rule validasi; hanya menghapus kerja ulang yang redundant.

## 30. Quotation Validate Full-Page Submit Spinner (2026-04-20)

Masalah:
- Saat user klik `Validate Quotation`, proses submit non-AJAX tidak memberi feedback loading tingkat halaman, sehingga terlihat seperti tombol tidak merespons.

Perbaikan:
- Menambahkan full-page loading overlay di halaman validation quotation.
- Overlay ditampilkan saat form finalize (`quotation-finalize-form`) disubmit.
- Alur finalize tetap non-AJAX; AJAX hanya dipakai untuk validate per-item.

Dampak:
- UX lebih jelas saat menunggu proses finalize + redirect.
- Mengurangi kebingungan user pada koneksi lambat atau quotation besar.

Adjustment:
- Tombol `Validate Quotation` tidak lagi menampilkan spinner di dalam tombol.
- Finalize kini diperlakukan seperti submit form biasa:
  - trigger submit normal ke `quotation-finalize-form`,
  - tampilkan spinner global layout (`.page-spinner`) saat submit,
  - disable tombol aksi untuk mencegah double submit.
- Removed:
  - overlay spinner lokal di halaman validation untuk mencegah double overlay dengan spinner global.

## 31. Quotation Detail Validation Progress - Multi Validator Visibility (2026-04-20)

Masalah:
- Pada halaman detail quotation, card `Validation Progress` belum menampilkan informasi siapa saja user validator yang sudah melakukan validasi.

Perbaikan:
- Menambahkan agregasi validator di `QuotationValidationService::getProgress()` berdasarkan item validasi yang sudah tervalidasi (`validated_by`):
  - daftar validator unik,
  - jumlah item tervalidasi per validator,
  - waktu validasi terakhir per validator.
- Menampilkan daftar tersebut pada card `Validation Progress` di halaman detail quotation (`quotations.show`).

Dampak:
- Informasi validasi menjadi lebih jelas saat ada lebih dari satu validator.
- User dapat melihat kontribusi masing-masing validator langsung dari halaman detail quotation.


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
- `database/seeders/ProjectBaselineSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/FeatureAccessSeeder.php`
- `database/seeders/PermissionSeeder.php`
- `database/seeders/PermissionBaselineSeeder.php`
- `app/Http/Controllers/Sales/InquiryController.php`
- `resources/views/modules/inquiries/index.blade.php`
- `app/Models/Inquiry.php`
- `app/Policies/InquiryPolicy.php`
- `resources/views/pdf/itinerary.blade.php`
- `resources/views/pdf/quotation_with_itinerary.blade.php`
- `resources/views/modules/quotations/validate.blade.php`
- `resources/views/modules/itineraries/_form.blade.php`
- `app/Http/Controllers/Admin/ItineraryController.php`
- `resources/views/modules/itineraries/_form.blade.php`
- `app/Http/Controllers/Admin/IslandTransferController.php`
- `resources/views/modules/island-transfers/index.blade.php`
- `resources/views/modules/island-transfers/create.blade.php`
- `resources/views/modules/island-transfers/edit.blade.php`
- `resources/views/modules/island-transfers/show.blade.php`
- `resources/views/modules/island-transfers/_form.blade.php`
- `resources/views/modules/island-transfers/create.blade.php`
- `app/Http/Controllers/Admin/IslandTransferController.php`
- `lang/en/ui.php`
- `resources/views/modules/island-transfers/_form.blade.php`
- `docs/technical/ISLAND_TRANSFER_MODULE.md`

## Catatan Governance

Status detail dan perubahan harian resmi tetap dicatat di:
- `VOYEX_CRM_SYSTEM_ROADMAP.md` bagian `CHANGELOG (LATEST)`
