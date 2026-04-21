# Dashboard Async Loading Standard

Last Updated: 2026-04-21

Dokumen ini menjadi referensi teknis untuk implementasi dashboard yang terasa cepat dengan pola `shell-first + async widgets`.

## Objective

1. Perceive halaman "langsung terbuka" saat navigasi.
2. Data tiap section tampil bertahap segera setelah siap.
3. Menghindari bottleneck render SSR besar tunggal.

## Reference Implementation

Pilot implementasi:

- Controller: `app/Http/Controllers/Administrator/DashboardController.php`
- View shell: `resources/views/administrator/dashboard.blade.php`
- Partial widget:
  - `resources/views/administrator/dashboard/partials/system-management.blade.php`
  - `resources/views/administrator/dashboard/partials/operational-overview.blade.php`
  - `resources/views/administrator/dashboard/partials/master-data-catalog.blade.php`
  - `resources/views/administrator/dashboard/partials/pending-quotations.blade.php`
  - `resources/views/administrator/dashboard/partials/recent-users.blade.php`
  - `resources/views/administrator/dashboard/partials/_skeleton.blade.php`

## Endpoint Convention

Pattern route:

- `/administrator-dashboard/widgets/{section}`
- Route name: `dashboard.administrator.widget`

Response JSON minimum:

```json
{
  "ok": true,
  "section": "system-management",
  "html": "<div>...</div>"
}
```

## Caching Strategy

Gunakan cache per-user per-section untuk mengurangi query berat:

- key contoh: `administrator-dashboard:{userId}:system-kpis`
- TTL awal: `60s`

Catatan:
- TTL boleh disesuaikan per section.
- Untuk data sangat dinamis, gunakan TTL lebih pendek atau invalidasi event-based.

## Frontend Behavior

1. Render skeleton dulu di semua widget.
2. Fetch widget secara bertahap (queue), bukan serentak total.
3. Begitu payload widget siap, render widget tersebut tanpa menunggu widget lain.
4. Di dalam widget, item data (`data-progressive-item`) ditampilkan blur dahulu lalu clear per-item secara berurutan.
5. Jika gagal, tampilkan fallback error + tombol retry per-widget.
6. Ikuti standar global `progressive blur transition` untuk perpindahan antar-halaman (layout-level).

## Global Spinner Policy

Untuk dashboard async, jangan gunakan overlay spinner global saat navigasi.
Kebijakan global aplikasi:

1. Overlay spinner hanya untuk submit form.
2. Jika form dijalankan async/non-blocking, set `data-page-spinner="off"` pada form/wrapper.
3. Pada page root dashboard async, set:
   - `data-background-load-page="1"`
   - `data-page-spinner="off"`
4. Gunakan skeleton di masing-masing section sebagai loading indicator utama.

## Rollout Plan

1. Administrator Dashboard (done, pilot + async widget queue).
2. Manager Dashboard (done: progressive per-item blur reveal).
3. Reservation Dashboard (done: progressive per-item blur reveal).
4. Finance Dashboard (done: progressive per-item blur reveal).
5. Director Dashboard (done: progressive per-item blur reveal).
6. Modul index berat (next) bila diperlukan.
