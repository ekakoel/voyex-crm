# Editor Manual Item Validation Queue

## Tujuan
Menjaga kualitas master data yang dibuat cepat dari **Itinerary Day Planner** (manual create `Attraction`, `Activity`, `F&B`) dengan alur validasi terstruktur oleh role **Editor**.

## Ringkasan Flow
1. User non-editor membuat item manual dari Day Planner.
2. Sistem mencatat event ke `activity_logs`:
   - `module = itinerary_day_planner`
   - `action = manual_item_created`
   - `properties.requires_validation = true`
3. Editor menerima notifikasi bell + popup (polling setiap 20 detik).
4. Editor membuka halaman queue:
   - melihat daftar pending,
   - membuka item terkait,
   - klik **Mark as Validated**.
5. Sistem menandai log sebagai tervalidasi:
   - `properties.validated_at`
   - `properties.validated_by`
   - `properties.validated_by_name`
   - `properties.requires_validation = false`

## Route
- `GET itineraries/manual-item-notifications/poll`
  - Name: `itineraries.manual-item-notifications.poll`
  - Fungsi: hitung pending + latest item untuk notifikasi realtime.
- `GET itineraries/manual-item-validation-queue`
  - Name: `itineraries.manual-item-validation-queue`
  - Fungsi: halaman list pending validasi.
- `PATCH itineraries/manual-item-validation-queue/{activityLog}/validate`
  - Name: `itineraries.manual-item-validation-queue.validate`
  - Fungsi: mark log sebagai validated.

## Permission & Role
Queue ini didesain **permission-first**:
- wajib `dashboard.editor.view`
- wajib `module.itineraries.access`

Default role mapping:
- Role `Editor` harus memiliki `module.itineraries.access` (disinkronkan pada `RolePermissionSeeder`).

## Query Pending
Item dianggap **pending** jika:
- `activity_logs.module = itinerary_day_planner`
- `activity_logs.action = manual_item_created`
- `JSON_EXTRACT(properties, '$.validated_at') IS NULL`
- dan bukan item buatan editor itu sendiri (`user_id != current editor`).

## File yang Terkait
- `app/Http/Controllers/Admin/ItineraryController.php`
- `app/Http/View/SidebarComposer.php`
- `resources/views/layouts/master.blade.php`
- `resources/views/editor/manual-item-queue.blade.php`
- `routes/web.php`
- `database/seeders/RolePermissionSeeder.php`

## Catatan Operasional
- Setelah update permission/role di environment existing, jalankan:
  - `php artisan db:seed --class=PermissionSeeder --force`
  - `php artisan db:seed --class=RolePermissionSeeder --force`
- Notifikasi polling bersifat UI-level (browser session), bukan push queue worker.
