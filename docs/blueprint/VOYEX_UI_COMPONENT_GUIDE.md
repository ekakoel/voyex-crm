# VOYEX UI Component Guide

Last Updated: 2026-05-21
Scope: UI standard component reference

## Tujuan
- Menjadi referensi komponen reusable untuk seluruh module VOYEX CRM.
- Menjaga konsistensi visual, perilaku, dan i18n (`ui_phrase()`).

## Komponen Reusable UI (Standard Layer)
- `x-ui.page-header`
- `x-ui.status-badge`
- `x-ui.workflow-stepper`
- `x-ui.action-panel`
- `x-ui.empty-state`
- `x-ui.filter-bar`
- `x-ui.data-table`
- `x-ui.metric-card`
- `x-ui.info-card`
- `x-ui.timeline`
- `x-ui.section-card`
- `x-ui.lock-alert`
- `x-ui.money`
- `x-ui.date-display`
- `x-ui.module-tabs`
- `x-ui.table-action-dropdown`
- `map-standard-section`
- `location-map-picker`

## Komponen Wajib (Baseline)
- Workflow Stepper
- Status Badge
- Filter Bar
- Status Tabs
- Quick Action Panel
- Empty State
- Activity Timeline
- Summary/KPI Card
- Action Dropdown

## Workflow Stepper
- Fungsi: menampilkan progres lifecycle per module.
- Penempatan: detail page, di bawah page header.
- Input minimum:
  - daftar step
  - current step
- Aturan:
  - tampilkan done/current/upcoming state.
  - gunakan label `ui_phrase()`.

## Status Badge
- Fungsi: representasi visual status record.
- Aturan:
  - gunakan satu komponen shared.
  - hindari custom badge inline per page jika bisa pakai komponen.
  - warna mengacu ke standar status color.

## Filter Bar
- Fungsi: pencarian dan penyaringan data index.
- Isi umum:
  - keyword search
  - status filter
  - per_page
  - reset filter
- Aturan:
  - parameter query konsisten.
- UX cepat: minim submit friction.

## Map & Location Standard
- Gunakan `components.map-standard-section` untuk field URL map, koordinat, alamat, region, country, destination, dan status resolver.
- Gunakan `components.location-map-picker` sebagai engine reusable untuk Leaflet map picker lintas modul.
- Gunakan partial modul tipis sebagai wrapper icon dan hint per domain, bukan menulis ulang script Leaflet per modul.
- Kontrak perilaku:
  - input Google Maps URL penuh atau short link wajib bisa di-resolve melalui endpoint `location.resolve-google-map`,
  - hasil resolve mengisi `latitude`, `longitude`, `address`, `city`, `province`, `country`, `location`, `timezone`, dan `destination_id` jika tersedia,
  - marker map wajib langsung sinkron dengan `latitude` dan `longitude`,
  - drag marker wajib mengubah `latitude` dan `longitude` serta memperbarui map URL,
  - controller tetap wajib memanggil `LocationResolver` saat submit sebagai fallback server-side agar data tersimpan konsisten meskipun JavaScript gagal.
- Mapping icon standar:
  - `Destinations`: `fa-map-location-dot`
  - `Vendors / Providers`: `fa-store`
  - `Hotels`: `fa-hotel`
  - `Airports`: `fa-plane-departure`
  - `Tourist Attractions`: `fa-camera-retro`
  - `Company Settings`: `fa-building`

## Status Tabs
- Fungsi: segmentasi data berdasarkan status utama.
- Aturan:
  - tab "All" disediakan.
  - tab aktif harus jelas.
  - gunakan URL query agar shareable.
  - gunakan class standar global: `app-tabs` sebagai wrapper dan `app-tab` untuk item tab.
  - state aktif wajib menggunakan class `is-active` (atau `aria-selected="true"` untuk tab button).
  - hindari style tab ad-hoc per module (`btn-primary-sm` vs `btn-ghost-sm`) untuk menjaga konsistensi lintas halaman.

## Quick Action Panel
- Fungsi: kumpulan action prioritas sesuai status/permission.
- Aturan:
  - hanya action valid yang tampil.
- action destructive wajib confirm.

## Header Action Buttons (Required)
- Berlaku untuk `@section('page_actions')` pada semua detail/form/index page.
- Aturan wajib:
  - setiap tombol wajib memiliki icon relevan + label text,
  - icon bersifat dekoratif (`aria-hidden="true"`),
  - gunakan class tombol global (`btn-primary` / `btn-secondary` / `btn-ghost` / `btn-outline`),
  - visibility tetap permission + status based.
- Rekomendasi icon mapping:
  - Create/Add: `fa-plus` atau `fa-file-circle-plus`
  - Generate Quotation: `fa-file-invoice-dollar`
  - Generate PDF/Export PDF: `fa-file-pdf`
  - Edit: `fa-pen`
  - Duplicate: `fa-copy`
  - Delete: `fa-trash`
  - Back: `fa-arrow-left`

## Confirmation Modal Standard (Required)
- Semua action yang membutuhkan konfirmasi (`duplicate`, `delete`, `cancel`, `approve`, `reject`, dan aksi irreversible lain) wajib menggunakan modal konfirmasi standar.
- Dilarang menggunakan `confirm()` bawaan browser untuk flow utama module.
- Gunakan komponen reusable:
  - `x-ui.confirm-action`
- Kontrak minimum:
  - `title` jelas menyebut aksi,
  - `message` menjelaskan dampak,
  - `impactTitle` + `impactItems` untuk informasi konsekuensi action,
  - `noticeMessage` + `noticeTone` untuk notifikasi hasil/proses,
  - tombol `Cancel` + `Confirm`,
  - style modal konsisten dengan komponen global (`x-modal`).
- i18n standar:
  - default dan copy konfirmasi memakai namespace baru: `lang/{locale}/confirm.php`,
  - gunakan `__('confirm.*')` untuk teks di modal konfirmasi,
  - tetap gunakan `ui_phrase()` untuk label/action yang sudah menjadi kosakata umum UI.

Contoh baseline:

```blade
<x-ui.confirm-action
    :action="route('bookings.destroy', $booking)"
    method="DELETE"
    :title="ui_phrase('Delete') . ' ' . ui_phrase('Booking')"
    :message="ui_phrase('confirm delete')"
    :impact-title="__('confirm.important_warning')"
    :impact-items="[
        __('confirm.delete_itinerary_info_1'),
        __('confirm.delete_itinerary_info_2'),
    ]"
    :notice-message="__('confirm.notification_after_action')"
    notice-tone="danger"
    :confirm-label="ui_phrase('Delete')"
    :trigger-label="ui_phrase('Delete')"
    trigger-icon="fa-solid fa-trash w-4"
    trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50"
    confirm-class="btn-danger-sm"
/>
```

## Empty State
- Fungsi: panduan user saat data kosong.
- Konten minimum:
  - title
  - message
  - CTA opsional

## Activity Timeline
- Fungsi: jejak perubahan record.
- Aturan:
  - urut terbaru ke lama.
  - tampilkan actor, action, timestamp.

## Summary/KPI Card
- Fungsi: ringkasan angka penting pada module index/detail.
- Aturan:
  - hanya tampil jika relevan.
  - hindari metrik yang butuh query berat tanpa cache/agregasi.

## Action Dropdown
- Fungsi: merapikan banyak action baris table.
- Aturan:
  - semua action pada kolom table action menggunakan dropdown `...`.
  - `View/Detail` juga masuk dropdown agar kolom action konsisten dan compact.
  - action sekunder dipindah dropdown.
  - dropdown harus tertutup saat klik di luar area dropdown.
  - gunakan komponen: `x-ui.table-action-dropdown`.

## Modern Index Toolbar Pattern
- Gunakan satu filter card/toolbar compact per index page.
- Quick tabs/status/priority harus ditempatkan di area filter yang sama (bukan card terpisah).
- Pertahankan query param dan atribut AJAX/filter existing (`data-service-filter-*`) saat merapikan layout.

## Main Baseline Filter Standard (Required)
- Baseline utama seluruh index page: `Customers / Agents` (`resources/views/modules/customers/index.blade.php`).
- Aturan wajib implementasi lintas modul:
  - urutan halaman: KPI/Summary Cards jika relevan -> satu filter card utama -> data table/card list -> pagination/empty state,
  - halaman index modul tidak memakai sidebar; semua card pendukung harus dipindahkan ke area utama,
  - filter card harus tetap visible pada mobile/tablet, tidak boleh dibungkus `hidden md:block`,
  - pada desktop, filter input utama harus disusun sebagai satu baris horizontal selama jumlah field masih wajar,
  - hasil data harus menyediakan dual presentation: table untuk desktop dan card/list untuk mobile,
  - tanpa card di dalam card pada area filter,
  - input control mengikuti tinggi/radius `app-input`,
  - tombol `Reset` menggunakan `btn-secondary`, tinggi sejajar input, radius setara `app-input`,
  - filtering + pagination harus tetap AJAX (`data-service-filter-*`), tanpa full-page reload.
- Variasi field filter diperbolehkan per modul selama kontrak visual dan perilaku di atas tetap dipatuhi.

## Standar Label dan I18n
- Semua label UI menggunakan `ui_phrase()`.
- Hindari hardcoded string di Blade untuk elemen user-facing.

## Standar Money / Currency Component
- Gunakan komponen money existing untuk seluruh tampilan nominal:
  - `<x-money ... />` (existing baseline)
  - `<x-ui.money ... />` (wrapper standar UI)
- Jangan render nominal uang dengan `number_format` manual di Blade.
- Param minimum:
  - `amount` (boleh null, harus fallback aman)
  - `currency` opsional (default source currency `IDR`)
- Komponen wajib aman untuk:
  - table
  - card
  - summary
  - invoice
  - quotation
  - booking
  - payment

## Standar Translation Helper di Component
- Untuk text internal component (title/label/default message):
  - gunakan `ui_phrase()`.
- Untuk dynamic plural text:
  - gunakan `ui_choice()`.

## Catatan Implementasi
- Komponen UI tidak boleh query database.
- Komponen UI wajib menerima text dari caller (sudah translated di caller) untuk title/description/label/action jika memungkinkan.
- Komponen uang wajib lewat formatter existing (`x-money` / `Currency::format()`).
