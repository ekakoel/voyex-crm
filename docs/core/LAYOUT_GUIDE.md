# Layout Guide

Last Updated: 2026-04-21


Panduan ini khusus untuk standar layout halaman agar konsisten lintas modul.
Detail domain/business rule ada di `PROJECT_KNOWLEDGE_BASE.md`.

## 1. Header dan Breadcrumb

Master layout otomatis menampilkan:
- `page_title`
- `page_subtitle`
- breadcrumbs
- `page_actions`

Contoh:

```blade
@extends('layouts.master')

@section('page_title', 'Customers')
@section('page_subtitle', 'Manage customer data')
@section('page_actions')
    <a href="{{ route('customers.create') }}" class="btn-primary-sm">Add Customer</a>
@endsection
```

Jika header tidak diperlukan:

```blade
@section('page_header_hidden', '1')
```

## 2. Grid Baseline

Gunakan baseline berikut untuk halaman non-dashboard:

- Index: `4 / 8` (filter kiri, list kanan)
- Create/Edit/Detail: `8 / 4` (main kiri, sidebar kanan)
- Gap antar-card/container card: `gap-3` (`0.75rem`) sebagai rhythm global.

Class acuan di `resources/css/app.css`:
- `.module-grid-3-9`
- `.module-grid-4-8`
- `.module-grid-8-4`
- `.module-grid-main`
- `.module-grid-side`
- `.app-card-stack`
- `.app-card-row`

## 2a. Global Card Rhythm Standard

Semua halaman mengikuti rhythm card global:

- token utama: `--app-card-gap: 0.75rem`;
- class Tailwind equivalent: `gap-3`;
- berlaku untuk grid, flex row, stack, dashboard KPI, responsive card list, dan sidebar card group;
- wrapper lama yang masih memakai `gap-4`, `gap-5`, `gap-6`, atau `space-y-6` akan dinormalisasi oleh CSS global selama wrapper tersebut berisi card (`app-card`, `sa-card`, atau `module-card`).

Untuk halaman baru, gunakan salah satu pola berikut:

```blade
<div class="app-card-stack">
    <section class="app-card p-4">...</section>
    <section class="app-card p-4">...</section>
</div>
```

```blade
<div class="app-card-row">
    <section class="app-card p-4">...</section>
    <section class="app-card p-4">...</section>
</div>
```

Jika butuh grid kolom responsive, tetap gunakan `gap-3` secara eksplisit:

```blade
<div class="grid gap-3 lg:grid-cols-3">
    <section class="app-card p-4">...</section>
    <section class="app-card p-4">...</section>
</div>
```

## 3. Template Index (Filter + Results)

```blade
@section('content')
    <div class="space-y-6 module-page">
        <div class="module-grid-3-9">
            <aside class="module-grid-side app-card p-4">
                {{-- filter --}}
            </aside>

            <section class="module-grid-main space-y-4">
                {{-- stats --}}
                {{-- table / cards / pagination --}}
            </section>
        </div>
    </div>
@endsection
```

## 4. Template Create/Edit/Detail

```blade
@section('content')
    <div class="space-y-6 module-page">
        <div class="module-grid-8-4">
            <section class="module-grid-main">
                {{-- main form/detail --}}
            </section>

            <aside class="module-grid-side space-y-6">
                {{-- context / audit / map --}}
            </aside>
        </div>
    </div>
@endsection
```

## 5. Breakpoint Behavior

- `module-grid-8-4`: stack di mobile/tablet, split di `xl`.
- `module-grid-3-9` / `module-grid-4-8`: dipakai untuk pola index/list.
- Dashboard boleh punya layout sendiri.
- Semua halaman dirender di wrapper global `.app-page-shell` (master layout) agar tetap nyaman di desktop lebar tanpa membuat baris konten terlalu panjang.

## 5a. Responsive Content Standard (Required)

Untuk semua halaman module (existing maupun baru):

1. Mobile-first:
   - konten utama harus terbaca jelas di layar kecil tanpa zoom.
2. Tablet-friendly:
   - layout tidak boleh hanya mengandalkan table horizontal panjang.
3. Data-list adaptation:
   - desktop: table (`app-table`) diperbolehkan sebagai primary view.
   - mobile/tablet: sediakan mode card/list responsive jika table menjadi tidak usable.
4. Action parity:
   - semua action penting harus tersedia pada semua breakpoint (mobile/tablet/desktop).
5. State sync:
   - jika menggunakan AJAX per-row/per-item, status/progress harus sinkron pada semua variant layout.

## 5b. Reusable Responsive Classes (Global)

Gunakan class global ini agar implementasi responsive konsisten:

- Wrapper:
  - `.responsive-data-shell`
- Mobile/Tablet variant:
  - `.responsive-data-mobile`
  - `.responsive-group-card`
  - `.responsive-group-header`
  - `.responsive-item-card`
- Desktop variant:
  - `.responsive-data-desktop`
- Shared page blocks:
  - `.module-kpi-grid`
  - `.module-action-row`

Contoh pola wajib (table desktop + card mobile/tablet):

```blade
<div class="responsive-data-shell">
    <div class="responsive-data-mobile">
        {{-- grouped cards for mobile/tablet --}}
    </div>

    <div class="responsive-data-desktop overflow-x-auto">
        <table class="app-table w-full">
            {{-- desktop table --}}
        </table>
    </div>
</div>
```

Aturan breakpoint:
- mobile/tablet memakai card/list (`responsive-data-mobile`),
- desktop `xl` ke atas memakai table (`responsive-data-desktop`).

## 5c. Device Target Matrix (Required)

Gunakan baseline ini untuk semua modul:

- Small phone (`<= 480px`):
  - tidak boleh ada horizontal scroll pada body,
  - semua aksi utama tetap terlihat dan bisa ditekan.
- Mobile (`481px - 767px`):
  - gunakan satu kolom untuk form utama,
  - data list wajib pakai card/list variant.
- Tablet (`768px - 1279px`):
  - grid boleh mulai split, tapi tetap prioritaskan keterbacaan,
  - tabel besar tetap punya fallback card/list.
- Desktop (`1280px - 1919px`):
  - layout split penuh (index/detail) aktif,
  - tabel desktop menjadi primary view.
- Wide Desktop (`>= 1920px`):
  - konten tetap dibatasi wrapper (`.app-page-shell`) agar line-length dan scanning tetap nyaman.

## 5d. Responsive QA Minimum (Per Page)

Checklist cepat sebelum merge:

1. Tidak ada horizontal scroll di viewport.
2. Tombol action utama tersedia di mobile, tablet, desktop.
3. Filter/form tetap bisa dioperasikan satu tangan di mobile.
4. Tabel besar punya fallback card/list pada mobile/tablet.
5. Spacing, ukuran font, dan hierarchy tetap konsisten dengan class global (`app-*`, `module-*`, `responsive-*`).
6. Page header harus memiliki jarak aman dari sticky top navigation pada mobile. Baseline global: `main.app-content` memakai `pt-6` (`1.5rem`) sebelum page header dirender.

## 6. Map Standard Section

Modul dengan field Google Maps URL wajib pakai partial:
- `resources/views/components/map-standard-section.blade.php`

Urutan field standar:
1. Location on Map
2. Map URL
3. Latitude/Longitude
4. Address
5. City/Province
6. Country
7. Destination

Field non-standar (misal timezone/hidden khusus) diletakkan di luar partial.

## 7. Anti-Drift Rule

Agar layout tidak drift antar modul:
1. jangan buat class grid custom per halaman jika sudah ada utility global,
2. pakai komponen style global (`app-card`, `app-table`, `app-input`, `btn-*`),
3. untuk field nominal gunakan standar `x-money-input` dengan badge currency kiri (left affix),
4. untuk jarak antar-card gunakan baseline global `--app-card-gap: 0.75rem` (`gap-3`), bukan variasi lokal seperti `gap-5`, `gap-6`, atau `space-y-6` untuk wrapper card baru,
5. update dokumen ini jika baseline layout global berubah.

## 8. Async Dashboard Pattern (Recommended)

Untuk dashboard dengan banyak KPI/query:

1. Render shell halaman terlebih dulu (header, layout grid, card container, skeleton).
2. Load setiap section via endpoint widget terpisah (AJAX paralel), contoh:
   - `/administrator-dashboard/widgets/system-management`
   - `/administrator-dashboard/widgets/operational-overview`
   - `/administrator-dashboard/widgets/master-data-catalog`
3. Setiap widget mengembalikan JSON:
   - `ok` (boolean)
   - `section` (string)
   - `html` (rendered partial)
4. Frontend mengganti skeleton section secara independen begitu respons siap
   (jangan menunggu seluruh widget selesai).
5. Tambahkan cache singkat per-user/per-section (TTL 30-120 detik) untuk menurunkan latency query berat.

Standar penamaan partial dashboard:
- `resources/views/<module>/dashboard/partials/<section>.blade.php`
- skeleton shared partial: `_skeleton.blade.php`

## 9. Spinner Standard (Form-Only Overlay)

Mulai rollout performa 2026-04-21, overlay `page-spinner` memakai kebijakan global berikut:

1. Navigasi halaman/link tidak boleh memunculkan overlay spinner.
2. Spinner overlay hanya dipakai saat submit form.
3. Untuk form async/non-blocking, wajib set:
   - `data-page-spinner="off"`
4. Halaman async/background-load tetap wajib:
   - `data-background-load-page="1"`
   - skeleton lokal per-section sebagai indikator loading utama.
5. Jangan mengandalkan overlay spinner global untuk halaman list/index/dashboard modern.

## 10. Progressive Blur Transition Standard

Untuk meningkatkan persepsi performa saat klik link/navigation:

1. Klik link internal harus langsung mengganti konten ke shell tujuan (instant shell), lalu lanjut navigasi normal ke URL target.
2. Shell instant wajib menampilkan placeholder data per baris (contoh: `Name: Loading...`) dengan state blur.
3. Konten utama wajib dibungkus marker:
   - `data-page-progressive-content`
4. Saat pending, konten ditampilkan blur + slightly dim (`filter + opacity`), tanpa blocking interaksi global.
5. Saat halaman target siap (`window.load` atau fallback timeout), state dihapus dan konten kembali tajam.
6. Implementasi ini berlaku global di layout, bukan per-modul, agar konsisten lintas proyek.

## 11. Progressive Data Reveal Standard

Untuk halaman async (dashboard/index berat), gunakan aturan ini:

1. Data tidak dimunculkan serentak penuh, tetapi bertahap (queue/batch kecil).
2. Blok data yang sudah siap harus langsung tampil.
3. Blok data yang belum siap tetap blur per-item (bukan blur seluruh UI).
4. Gunakan marker `data-progressive-item` pada item data yang di-reveal.
5. Reveal dilakukan berurutan (stagger) agar user melihat progres loading nyata.
