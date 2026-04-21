# Performance Optimization Standard

Last Updated: 2026-04-21

Dokumen ini menjadi baseline performa aplikasi VOYEX CRM untuk request web, dashboard, dan shared infrastructure.

## 1. Objective

Target utama:

1. Mengurangi kerja berulang pada setiap request.
2. Menurunkan query/read yang sebenarnya bisa di-cache singkat.
3. Menjaga invalidation jelas saat data master berubah.
4. Memastikan optimasi aman terhadap flow bisnis dan permission.

## 2. Shared Request Baseline

Area yang wajib dianggap sensitif performa:

1. `View::composer` global (`layouts.master`, auth branding, stats composer).
2. Middleware yang dipanggil di hampir semua route.
3. Helper global yang sering dipanggil dari Blade (`currency`, module access, branding).
4. Dashboard dengan banyak aggregate query.

Aturan:

1. Hindari query langsung berulang di composer/layout jika data bisa dibagikan lewat cache singkat.
2. Hindari `Schema::hasTable()` / `Schema::hasColumn()` berulang-ulang dalam satu request.
3. Hindari write-side effect pada service yang dipanggil hampir di semua request.
4. Untuk data master yang jarang berubah, gunakan cache singkat atau cache dengan invalidation eksplisit.

## 3. Implemented Baseline (Current)

### 3.1 Schema inspection

- Gunakan `App\Support\SchemaInspector` untuk memoization `hasTable` / `hasColumn` per-request.
- Jangan memanggil `Schema::hasTable()` / `Schema::hasColumn()` berulang langsung di hotspot request jika helper ini sudah cukup.

### 3.2 Module state

- `App\Services\ModuleService` sekarang:
  - memakai cache untuk enabled map dan module list,
  - menghindari query module per item sidebar,
  - menyediakan `ModuleService::flushCache()` untuk invalidation setelah toggle module.
- Side-effect bootstrap default module tetap ada, tetapi dibatasi melalui cache bootstrap singkat agar tidak menulis DB di tiap request.

### 3.3 Currency metadata

- `App\Support\Currency` sekarang membaca metadata currency dari cache terpusat.
- Method `current()`, `rate()`, `meta()`, dan `activeOptions()` harus memakai baseline ini.
- Setelah create/update/delete/bulk update currency, wajib panggil `App\Support\Currency::flushCache()`.

### 3.4 Company settings / branding

- Branding data sekarang dibaca melalui `App\Support\CompanySettingsCache`.
- Composer auth dan sidebar tidak lagi query `company_settings` secara terpisah di setiap request.
- Setelah update company settings, wajib panggil `CompanySettingsCache::flush()`.

### 3.5 Sidebar composer

- Sidebar sekarang:
  - memakai enabled-module map yang sudah di-cache,
  - memakai company settings cache,
  - memakai currency options cache,
  - memakai cache singkat untuk quotation approval badge per user/role.

### 3.6 Index stats composer

- `IndexStatsComposer` memakai cache singkat (`120s`) untuk kartu statistik index page.
- Cocok untuk dashboard/list yang butuh angka cepat tetapi tidak harus real-time per detik.

### 3.7 Heavy dashboards

- Dashboard berat harus memakai salah satu:
  - `shell-first + async widgets`, atau
  - cache aggregate singkat untuk payload SSR.
- `SuperAdmin\DashboardController` dan trend endpoint sekarang memakai cache singkat untuk aggregate berat.

## 4. Invalidation Rules

Wajib flush cache pada titik perubahan berikut:

1. Module toggle:
   - `ModuleService::flushCache()`
2. Currency create/update/bulk update/delete:
   - `App\Support\Currency::flushCache()`
3. Company settings update:
   - `CompanySettingsCache::flush()`

Jika menambah shared cache baru, tulis juga:

1. siapa pembacanya,
2. kapan di-flush,
3. TTL jika tidak memakai invalidation eksplisit.

## 5. TTL Guidance

Panduan TTL:

1. Shared master metadata: `300s`
2. Sidebar notification / lightweight operational badge: `15-30s`
3. Index stats / dashboard KPI: `60-120s`
4. Trend data / aggregate berat: `45-120s`

Jangan pakai TTL terlalu panjang jika data memengaruhi keputusan operasional yang butuh near-real-time.

## 6. Anti-Pattern (Do Not Reintroduce)

1. Query `firstOrCreate()` / `givePermissionTo()` di service yang dipanggil hampir semua request tanpa cache guard.
2. Query yang sama di beberapa composer untuk data master yang sama.
3. `Schema::*` berulang di loop besar atau helper Blade yang sering dieksekusi.
4. Dashboard SSR besar tanpa cache atau async split.
5. Query count/status summary duplikatif pada page yang sama tanpa reuse.

## 7. Verification Checklist

Sebelum merge perubahan performa:

1. `php artisan optimize:clear`
2. `php artisan view:cache`
3. `php artisan route:list`
4. Syntax check file yang berubah (`php -l ...`)
5. Cek flow update data yang harus flush cache:
   - module toggle,
   - currency update,
   - company settings update.

## 8. Next Recommended Rollout

Area berikutnya yang layak dioptimasi bila dibutuhkan:

1. Super Admin dashboard aggregate lain yang masih SSR-heavy.
2. Filter option cache untuk module index yang sering membuka master list besar.
3. Quotation/Inquiry list stats yang masih menghitung banyak counter khusus di controller.
4. Audit query review untuk page detail paling berat (`itinerary`, `quotation validation`, `service map`).
