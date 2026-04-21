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

Class acuan di `resources/css/app.css`:
- `.module-grid-3-9`
- `.module-grid-4-8`
- `.module-grid-8-4`
- `.module-grid-main`
- `.module-grid-side`

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
4. update dokumen ini jika baseline layout global berubah.
