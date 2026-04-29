# Island Transfer Module

Last Updated: 2026-04-29

## Tujuan

Memisahkan domain `Island Transfer` dari modul `Activities` agar:
- tidak mencampur service activity umum dengan transport laut antar pulau,
- bisa menyimpan departure/arrival point secara eksplisit,
- bisa menyimpan lintasan laut (`route_geojson`) per transfer.

## Struktur Data

1. `island_transfers` (master):
- `vendor_id`
- `name`, `transfer_type`
- `departure_point_name`, `departure_latitude`, `departure_longitude`
- `arrival_point_name`, `arrival_latitude`, `arrival_longitude`
- `route_geojson` (LineString)
- `duration_minutes`, `capacity_min`, `capacity_max`, `notes`, `is_active`

2. `itinerary_island_transfers` (pivot itinerary):
- `itinerary_id`, `island_transfer_id`
- `day_number`, `pax`
- `start_time`, `end_time`
- `travel_minutes_to_next`, `visit_order`

## Integrasi Itinerary

- Form create/edit itinerary memakai payload terpisah:
  - `itinerary_activity_items[...]` untuk activity biasa
  - `itinerary_island_transfer_items[...]` untuk transfer antar pulau
- Sync backend juga dipisah:
  - `syncItineraryActivities()`
  - `syncItineraryIslandTransfers()`

## Integrasi Quotation + Validation + PDF

### Sumber Item Quotation

- Item quotation otomatis dibentuk dari itinerary oleh `ItineraryQuotationService`.
- Untuk island transfer:
  - `serviceable_type` diset ke `App\Models\IslandTransfer`,
  - `itinerary_item_type` diset ke `transfer`,
  - `serviceable_id` mengarah ke `island_transfers.id`.

### Canonical Value (Wajib Konsisten)

Nilai berikut harus konsisten antara form, JS, controller, dan DB agar tidak muncul error validasi seperti `The selected items.*.serviceable_type is invalid`:

1. `serviceable_type` yang valid mencakup:
- `App\Models\TouristAttraction`
- `App\Models\Activity`
- `App\Models\FoodBeverage`
- `App\Models\IslandTransfer`
- `App\Models\TransportUnit`
- `App\Models\HotelRoom`

2. `itinerary_item_type` yang valid mencakup:
- `transport_day`
- `attraction`
- `activity`
- `transfer`
- `fnb`
- `hotel_day_end`
- `manual`

### Quotation Validation Scope

- `IslandTransfer` termasuk item yang wajib validasi quotation.
- Update rate dari halaman validasi akan sinkron ke:
  - `quotation_items`,
  - master `island_transfers` (`contract_rate`, `markup_type`, `markup`, `publish_rate`),
  - `service_rate_histories`.

### PDF Itinerary dan PDF Quotation

- Schedule itinerary di PDF tetap menampilkan item transfer sebagai bagian urutan hari.
- Untuk tampilan ringkas:
  - kolom `Item` tidak lagi merender blok `description` panjang,
  - aturan ini berlaku konsisten pada:
    - `resources/views/pdf/itinerary.blade.php`,
    - `resources/views/pdf/quotation_with_itinerary.blade.php`.

## Integrasi Sidebar & Permission Bootstrap

- Sidebar item: `Service Items -> Island Transfers` (route `island-transfers.index`, module key `island_transfers`).
- Karena sidebar difilter oleh `module` + permission, module ini sekarang dibantu auto-bootstrap di `ModuleService`:
  - membuat record module `island_transfers` jika belum ada,
  - memastikan permission `module.island_transfers.*` tersedia,
  - memberikan permission ke role default: Administrator, Super Admin, Manager, Marketing, Reservation, Editor.
- Tujuan: mencegah menu hilang di environment baru saat seed belum sempat dijalankan.

## Integrasi Service Map

- `Service Map` (`services.map`) sekarang memuat Island Transfer sebagai layer khusus:
  - marker departure (`fa-ship`),
  - marker arrival (`fa-anchor`),
  - route polyline antar titik transfer.
- Sumber route:
  - prioritas `route_geojson` dari `island_transfers`,
  - fallback garis departure -> arrival jika `route_geojson` belum tersedia.
- Layer ini bisa ditoggle lewat legend type `Island Transfers` dan toggle berlaku untuk:
  - marker transfer,
  - polyline route transfer.
- Payload route disuplai dari backend (`routes`) dan dirender oleh `resources/js/service-map.js`.

## UI/UX Standardisasi Modul

- Halaman `index` sudah memakai pola standar service module:
  - `module-grid-3-9`,
  - panel filter kiri + result kanan,
  - desktop table + mobile cards,
  - status badge konsisten.
- Halaman `create/edit/show` sudah diselaraskan ke paket visual modul lain:
  - `module-page--island-transfers`,
  - `module-grid-8-4`,
  - `module-form-wrap` untuk create/edit,
  - sidebar `Quick Actions`, `Vendor Information`, dan `Audit Info` di detail.

## Google Maps URL Auto-Fill (Departure/Arrival)

- Pada form `create/edit`, section `Departure Point` dan `Arrival Point` kini memiliki field:
  - `Departure Google Maps URL`
  - `Arrival Google Maps URL`
- Tombol `Auto Fill Coordinates` akan parsing URL dan mengisi otomatis field:
  - latitude
  - longitude
- Format URL yang didukung:
  - query `?q=lat,lng` / `?query=lat,lng` / `?ll=lat,lng`
  - path/hash dengan pola `@lat,lng`
  - pola `!3dlat!4dlng`
- Fitur ini hanya membantu prefill pada UI form, tidak menambah kolom baru di database.

## Catatan Map

- Marker transfer memakai type `transfer` (ikon kapal).
- Untuk transfer, titik departure/arrival dipakai langsung dari master `island_transfers`.
- Jika `route_geojson` tersedia, lintasan transfer bisa dirender tanpa dipaksa mengikuti routing darat.
