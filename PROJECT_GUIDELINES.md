# Voyex CRM Project Guidelines

Last Updated: 2026-05-18

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
5. Untuk modul transaksi berbasis owner, aksi mutasi `update/delete` wajib ownership-based dengan permission check:
   - wajib lolos permission terkait, dan
   - wajib lolos ownership check sesuai policy modul.
   - untuk `Inquiry`: `creator-only` dapat memutasi inquiry.
   - untuk `Itinerary`, `Quotation`, dan `Booking`: tetap `creator-only`.
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

## 4e. Standar Format Tanggal/Waktu (Wajib)

1. Format tampilan tanggal wajib `YYYY-MM-DD`.
2. Format tampilan tanggal+waktu wajib `YYYY-MM-DD (HH:ii)`.
3. Aturan ini berlaku untuk seluruh UI, termasuk halaman dashboard, detail/list modul, dan PDF.
4. Dilarang menggunakan format relatif seperti `diffForHumans()` untuk tanggal utama yang ditampilkan user.
5. Gunakan formatter terpusat `\App\Support\DateTimeDisplay` untuk output tanggal/waktu di Blade/PDF.
6. Untuk render tanggal via JavaScript, gunakan format deterministic (`en-CA` + `formatToParts`) agar tidak tergantung locale browser.

## 4f. CI Guard Tanggal/Waktu (Wajib)

1. Setiap PR wajib lolos workflow `Date Format Guard`.
2. Guard script berada di `scripts/ci/check-date-format.sh`.
3. Script ini memblokir pattern non-standar pada layer UI/PDF (contoh: `diffForHumans()`, format `d M Y`, render locale-dependent datetime).
4. Jalankan lokal sebelum push:
   - `bash scripts/ci/check-date-format.sh`

## 4g. Standar Performa Shared Layer (Wajib)

1. Area yang berjalan di hampir semua request (`View::composer`, middleware module/permission, helper global, layout master) tidak boleh melakukan query berulang tanpa cache atau memoization.
2. Gunakan `App\Support\SchemaInspector` untuk pengecekan schema berulang pada hot path.
3. Gunakan cache shared yang sudah tersedia:
   - `ModuleService` untuk module enabled map/list,
   - `App\Support\Currency` untuk metadata currency,
   - `CompanySettingsCache` untuk company branding/settings.
4. Setiap cache shared wajib punya invalidation jelas pada mutation flow:
   - module toggle -> `ModuleService::flushCache()`,
   - currency mutation -> `App\Support\Currency::flushCache()`,
   - company settings update -> `CompanySettingsCache::flush()`.
5. Dashboard atau halaman agregat berat wajib memakai cache aggregate singkat, async widgets, atau progressive reveal sesuai `docs/technical/PERFORMANCE_OPTIMIZATION_STANDARD.md`.
6. Hindari side-effect DB write pada service yang dipanggil dari layout/sidebar/middleware tanpa guard cache yang eksplisit.

## 4h. Standar Multi-language / I18N (Wajib)

1. Semua perubahan UI dianggap wajib support multi-language secara default, tanpa perlu instruksi ulang.
2. Dilarang menambah text user-facing hardcoded di Blade/JS inline/controller response message.
3. Semua label, button, title, subtitle, modal text, helper text, option text, empty/error state wajib menggunakan `ui_phrase(...)` atau key i18n yang setara.
4. Setiap penambahan phrase baru wajib di-sync minimal ke:
   - `lang/en/ui_core.php`
   - `lang/zh_Hant/ui_core.php`
   - `lang/zh_Hans/ui_core.php`
5. Pull request/perubahan tidak boleh dianggap selesai jika masih ada text user-facing yang belum bisa diterjemahkan.
6. Untuk review perubahan UI, i18n compliance menjadi checklist wajib bersama responsive, permission, dan performa.
7. Setiap update WAJIB melakukan audit menyeluruh terhadap semua text/paragraf/kalimat user-facing pada scope perubahan:
   - Blade template,
   - string di JavaScript inline/module,
   - message controller/validation/flash,
   - label tabel/form/modal/empty-state/helper text.
8. Audit i18n wajib memastikan tidak ada hardcoded text baru yang lolos; semua kalimat harus terhubung ke translation key/`ui_phrase(...)`.
9. Perubahan dianggap belum selesai jika audit i18n belum dilakukan dan belum dinyatakan lulus pada laporan update.

## 4i. Standar Notifikasi CRUD (Wajib)

1. Setiap aksi `create`, `update`, `delete`, `activate/deactivate`, dan mutasi status wajib menampilkan notifikasi hasil ke user.
2. Controller wajib mengirim flash message standar:
   - sukses: `->with('success', ...)`
   - gagal/ditolak: `->with('error', ...)`
3. Pesan notifikasi wajib jelas, action-oriented, dan konsisten dengan outcome (contoh: `Booking updated successfully.`).
4. Semua halaman list/detail/form modul wajib merender flash message `success/error` (atau menggunakan shared partial global yang setara).
5. Untuk alur AJAX/modal, wajib tampilkan feedback visual setara (toast/alert inline) dengan level `success/error`.
6. Untuk UI multi-language, pesan notifikasi user-facing wajib menggunakan phrase i18n (`ui_phrase(...)` atau key translation setara), bukan hardcoded.

## 4j. Standar Persistensi Filter Index (Wajib)

1. Untuk halaman index/list modul, pilihan filter user (contoh: `q`, `status`, `destination`, `per_page`) wajib dipertahankan saat user kembali dari halaman lain (show/edit/create) atau saat refresh.
2. Persistensi filter harus menggunakan state server-side yang konsisten (disarankan `session`) per modul/per halaman.
3. Tombol `Reset/Clear Filter` wajib:
   - menghapus seluruh state filter tersimpan modul terkait,
   - mengembalikan daftar ke kondisi default awal (tanpa filter tersimpan),
   - mengembalikan `per_page` ke default sistem modul.
4. Implementasi reset tidak boleh bergantung pada manipulasi UI saja; reset harus memicu clear state di backend.
5. Setelah reset berhasil, state berikutnya harus dianggap fresh/default sampai user memilih filter lagi.

## 4k. Standar Trigger & Minimum Karakter Filter Text (Wajib)

1. Semua input filter bertipe text/search wajib memiliki minimum 3 karakter sebelum filter dianggap valid.
2. Untuk input filter text, begitu panjang input mencapai minimal 3 karakter, filter harus langsung terpicu otomatis (live trigger).
3. Trigger tambahan seperti `Enter`, `Tab`, `blur`, atau submit eksplisit tetap harus didukung sebagai fallback UX, tetapi tidak boleh menurunkan aturan live trigger pada minimum 3 karakter.
4. Jika karakter input text < 3 (dan tidak kosong), backend wajib menganggap pencarian tidak valid dan tidak menampilkan hasil match.
5. Aturan ini berlaku lintas modul untuk semua halaman index/list yang memakai filter text.
6. Saat user menginstruksikan "sesuaikan aturan filter", implementasi default harus mengikuti standar 4j + 4k tanpa perlu redefinisi ulang.

## 4m. Standar Utama Filter Index (Wajib)

1. Baseline utama struktur halaman index project adalah implementasi `Customers / Agents` (`resources/views/modules/customers/index.blade.php`).
2. Semua halaman index/list modul WAJIB mengikuti urutan visual baseline tersebut:
   - KPI/Summary Cards di bagian atas jika relevan,
   - satu filter card utama (compact) tepat di bawah KPI,
   - hasil data/list di bawah filter card,
   - tidak boleh memiliki sidebar kanan/kiri khusus halaman index modul,
   - seluruh konten index harus menggunakan layout full-width pada area utama,
   - filter card wajib selalu tampil pada mobile, tablet, dan desktop,
   - pada desktop, filter input utama harus diusahakan satu baris horizontal selama jumlah field masih wajar,
   - hasil data wajib memakai pola desktop table + mobile card list,
   - tanpa card bersarang di area filter,
   - action `Reset` sejajar tinggi dengan input form,
   - style tombol `Reset` menggunakan `secondary` dan radius setara input (`app-input`).
3. Untuk interaksi data, WAJIB menggunakan pola AJAX filter existing (`data-service-filter-*`) agar filtering dan pagination tidak melakukan full-page reload.
4. Input filter bersifat fleksibel per modul:
   - field filter boleh berbeda sesuai kebutuhan bisnis modul,
   - namun layout, ritme visual, dan perilaku interaksi wajib konsisten mengikuti baseline.
5. Dilarang menambahkan elemen dekoratif yang tidak esensial pada filter card (judul/deskripsi/tab tambahan) jika fungsi yang sama sudah tercover oleh input filter utama.
6. Semua implementasi/penyesuaian filter index baru harus mengacu ke standar ini sebagai patokan default, kecuali ada keputusan UX khusus yang terdokumentasi.

## 4l. Standar Booking Module (Wajib)

1. `Cancellation Policy` dan `Cancellation Fee Rules` wajib dipisahkan:
   - policy text untuk referensi manusia,
   - rules terstruktur untuk prefill/perhitungan operasional.
2. Untuk service item hotel (`HotelRoom`), cancellation fee policy wajib mengikuti level `Hotel` (bukan per room).
3. Nama service item pada UI Booking wajib menyertakan konteks provider:
   - `service name | vendor/provider`,
   - khusus hotel: `service name | hotel name`.
4. Input nominal booking/cancellation fee di UI mengikuti currency aktif user, namun persistensi DB wajib canonical IDR.
5. Query fallback policy dan kalkulasi data turunan berat wajib diprecompute di controller/service; dilarang query database di loop Blade booking.
6. Booking item historical snapshot (booking log/voucher/booking item) tidak boleh berubah retroaktif hanya karena master provider contact berubah.

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
- Performa: `docs/technical/PERFORMANCE_OPTIMIZATION_STANDARD.md`
- Itinerary detail teknis: `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`, `docs/technical/ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
- UAT quotation approval: `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`
- UAT quotation validation: `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md`
- Ringkasan fix teknis: `docs/technical/TECHNICAL_FIX_NOTES.md`
