# Layout Guide

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
3. update dokumen ini jika baseline layout global berubah.
