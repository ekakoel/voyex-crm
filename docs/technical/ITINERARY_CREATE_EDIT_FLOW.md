# Itinerary Create/Edit Flow

Last Updated: 2026-04-20


Dokumen ini menjelaskan flow create/edit itinerary secara end-to-end tanpa mengulang detail map show-page.

Referensi kode utama:
- `resources/views/modules/itineraries/create.blade.php`
- `resources/views/modules/itineraries/edit.blade.php`
- `resources/views/modules/itineraries/_form.blade.php`
- `app/Http/Controllers/Admin/ItineraryController.php`

Scope dokumen ini:
- create/edit form,
- normalisasi payload,
- aturan bisnis utama,
- area risiko saat refactor.

Di luar scope:
- arsitektur map pada halaman detail (`show`) -> lihat `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`.

## 1. Struktur Halaman

`create.blade.php` dan `edit.blade.php` berfungsi sebagai wrapper layout.

- Kolom kiri: form utama itinerary (`_form.blade.php`).
- Kolom kanan: panel pendukung seperti Inquiry Detail, Route Preview, dan Audit Info (edit).

Perbedaan create vs edit:
- Create submit ke `itineraries.store`.
- Edit submit ke `itineraries.update` (`PUT`).
- Edit membawa existing itinerary + relasi untuk prefill.

## 2. Tanggung Jawab Controller

### `create(Request $request)`

Controller memuat seluruh master data untuk form, termasuk:
- attractions,
- activities (+ vendor location),
- food & beverage (+ vendor location),
- hotels + rooms,
- airports,
- transport units,
- destinations,
- inquiries (+ customer/assigned/latest follow-up).

### `edit(Itinerary $itinerary)`

Sebelum render edit, controller melakukan guard:
- authorization update,
- lock untuk itinerary `final`,
- lock saat quotation terkait sudah `approved`.

Lalu memuat relasi itinerary existing:
- itinerary items,
- activity items,
- food-beverage items,
- day points,
- transport units,
- inquiry link.

### `store()` dan `update()`

Pola umum keduanya:
- validasi field itinerary utama,
- validasi array day-level dan schedule item,
- normalisasi day points,
- normalisasi daily transport units,
- sync relasi itinerary ke tabel terkait.

Tambahan flow penting:
- create memaksa `status = draft` dan isi `created_by`.
- destination dapat di-resolve ke `destination_id`.
- inquiry terkait bisa auto-transisi `draft -> processed` (sesuai rule status).

## 3. Persiapan Data di `_form.blade.php`

Form membuat state awal dari dua sumber:
1. `old(...)` saat validasi gagal,
2. data itinerary existing saat edit.

Schedule item dari 4 sumber digabung dulu menjadi satu state row:
- `itinerary_items` (attraction),
- `itinerary_activity_items` (activity),
- `itinerary_island_transfer_items` (island transfer dari modul khusus),
- `itinerary_food_beverage_items`.

Setelah itu row diurutkan dan dikelompokkan per hari (`rowsByDay`) untuk render DOM dinamis.

Day-level state juga dipersiapkan:
- start/end point + room,
- daily transport unit,
- include/exclude,
- main experience,
- hidden payload hotel stays.

## 4. Anatomy Day Section

Setiap day section minimal berisi:
- header day,
- `Start Tour` dan `End Tour` (auto kalkulasi),
- tombol `Add Item`,
- start point,
- list `schedule-row`,
- end point,
- daily transport unit,
- include/exclude,
- hidden fields untuk payload.

Point harian memakai domain:
- `hotel`,
- `airport`,
- `previous_day_end` (khusus start point day > 1).

Jika type = `hotel`, selector room aktif sesuai hotel terpilih.

## 5. Anatomy `schedule-row`

Setiap row schedule mewakili satu item itinerary di satu hari.

Komponen inti row:
- drag handle,
- sequence badge,
- item type,
- region/city filter,
- selector item (attraction/activity/island transfer/fnb),
- start-end time (hasil kalkulasi),
- main-experience toggle,
- remove action,
- hidden travel minutes + hidden day/order.
- hint otomatis antar-pulau jika ada perpindahan lintas area tanpa item transfer.

Catatan penting:
- row tidak selalu punya `name` input sejak awal,
- `name` final dibangun ulang oleh JS (`reindex()`) sebelum submit.

## 6. Pipeline JavaScript Utama

### 6.1 Selection dan mode row

- `getRowSelection(row)`: menentukan selector aktif berdasarkan type + value.
- `toggleType(row, type)`: switch mode attraction/activity/transfer/fnb dan reset state terkait.

### 6.2 Kalkulasi waktu

- `recalcDay(section)`: hitung urutan, start/end time item, dan end-time harian.
- `recalcAll()`: jalankan kalkulasi untuk semua hari secara berurutan.

### 6.3 Pembentukan payload submit

- `reindex()` adalah fungsi kritis.
- Fungsi ini memberi nama field final untuk 3 kelompok payload:
  - `itinerary_items[...]`,
  - `itinerary_activity_items[...]`,
  - `itinerary_island_transfer_items[...]`,
  - `itinerary_food_beverage_items[...]`.

Tanpa `reindex()` yang benar, payload backend akan tidak sinkron.

### 6.4 Aturan day point dan visibility

- `syncDayPointOptionRules()`: aturan day-dependent (`previous_day_end`, naming per-day).
- `syncPointItemVisibility()`: filter option berdasarkan type + destination + room availability.
- `syncMainExperienceSelection()`: hanya satu main experience per day.
- Item type `transfer` tidak boleh dijadikan main experience (highlight otomatis dinonaktifkan).
- Untuk `transfer`, sumber data bukan dari modul Activities, melainkan modul `Island Transfers` terpisah.

### 6.5 Clone, sorting, dan submit

- `cloneRow()` + `bindRow()`: menambahkan row baru dengan listener lengkap.
- `initSortable()`: drag-drop reorder row.
- Submit pipeline standar:
  1. `recalcAll()`
  2. `reindex()`
  3. sync hidden payload (hotel stays)
  4. validasi frontend
  5. submit form.

## 7. Payload ke Backend

### Layer utama itinerary
- title,
- destination/destination_id,
- inquiry_id,
- duration days/nights,
- description,
- status/is_active.

### Layer schedule item
- `itinerary_items`
- `itinerary_activity_items`
- `itinerary_island_transfer_items`
- `itinerary_food_beverage_items`

### Layer day-level
- start/end point arrays,
- start time + travel minute,
- includes/excludes,
- main experience,
- daily transport units,
- hotel stays hidden payload.

## 8. Persistensi dan Normalisasi

Di controller, payload diproses dengan pola:
1. validasi,
2. `normalizeDayPoints()`,
3. `normalizeDailyTransportUnits()`,
4. sync relasi itinerary.

Hasil akhirnya disimpan ke struktur relasional terpisah (bukan satu tabel flat).

## 9. Aturan Bisnis Kritis

- `duration_nights` tidak boleh melebihi `duration_days`.
- end point wajib valid untuk setiap hari.
- itinerary `final` tidak boleh dimutasi.
- itinerary dengan quotation `approved` tidak boleh diedit.
- inquiry terkait dapat berubah dari `draft` ke `processed` setelah save.
- item F&B menyimpan `meal_type` otomatis dari `start_time`:
  - `< 11:00` => `Breakfast`
  - `11:00 - 15:59` => `Lunch`
  - `>= 16:00` => `Dinner`

## 10. Known Risk Saat Refactor

1. Ketergantungan tinggi pada DOM-state + `reindex()`.
2. Mismatch validasi transport unit (`transport_unit_id` vs rule tabel) harus diverifikasi sebelum refactor besar.
3. Clone/sort day-row dapat memicu drift jika listener atau naming tidak ikut terpasang.

## 11. Hubungan dengan Map

- Create/edit map adalah preview berbasis DOM state form.
- Untuk segment transfer antar pulau:
  - preview memprioritaskan `route_geojson` dari master `island_transfers`,
  - jika tidak ada `route_geojson`, barulah fallback ke route darat (OSRM) per segmen.
- Show-page map adalah renderer berbasis data tersimpan.

Karena itu detail map show-page dipisah di:
- `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`.

## 12. QA Checklist Cepat

1. Buat itinerary baru multi-day dan submit.
2. Edit itinerary existing, cek prefill dan submit ulang.
3. Uji type switch item (`attraction/activity/fnb`) + reordering.
4. Uji skenario lintas area dan pastikan warning inter-island muncul jika transfer belum ditambahkan.
5. Uji start/end point airport-hotel termasuk room filtering.
6. Uji perubahan duration days (clone/remove section).
7. Pastikan tidak ada JS error saat map preview, reindex, dan submit.
8. Pastikan payload tersimpan konsisten di detail itinerary setelah redirect.
