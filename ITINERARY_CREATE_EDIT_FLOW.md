# Itinerary Create/Edit Flow

Dokumen ini merangkum alur halaman create/edit itinerary berdasarkan pembacaan langsung terhadap:

- `resources/views/modules/itineraries/create.blade.php`
- `resources/views/modules/itineraries/edit.blade.php`
- `resources/views/modules/itineraries/_form.blade.php`
- `app/Http/Controllers/Admin/ItineraryController.php`

Tujuan dokumen ini adalah membuat alur sistem create/edit itinerary dapat dibayangkan end-to-end tanpa perlu menebak.

Catatan status refactor:

- istilah resmi yang dipakai sistem sekarang adalah `hotel`
- alur create/edit itinerary sudah dibersihkan agar konsisten memakai `hotel` tanpa istilah legacy

## 1. Struktur Halaman

`create.blade.php` dan `edit.blade.php` hanya berfungsi sebagai wrapper layout.

- Kolom kiri berisi `<form>` utama.
- Isi form sebenarnya seluruhnya berasal dari include `modules.itineraries._form`.
- Sidebar kanan berisi:
  - `Inquiry Detail`
  - `Itinerary Route Preview`
  - `Route Debug`
  - khusus `edit.blade.php`, tambahan `Audit Info`

Perbedaan utama create vs edit:

- Create submit ke `route('itineraries.store')`
- Edit submit ke `route('itineraries.update', $itinerary)` dengan `@method('PUT')`
- Edit menerima objek `$itinerary` dan menampilkan audit info

## 2. Tanggung Jawab Controller

### `create(Request $request)`

Controller memuat semua master data yang dibutuhkan form:

- tourist attractions
- activities + vendor location
- food & beverage + vendor location
- hotels + rooms
- airports
- transport units
- destinations
- inquiries + customer + assigned user + latest follow up

Lalu view create dipanggil dengan data tersebut.

### `edit(Itinerary $itinerary)`

Controller melakukan guard sebelum edit:

- hanya creator/authorized user yang boleh update
- itinerary final tidak boleh diedit
- itinerary dengan quotation approved tidak boleh diedit

Lalu controller load:

- itinerary attractions
- itinerary activities
- itinerary food beverages
- itinerary transport units
- day points
- inquiry
- arrival/departure transport

Setelah itu master data yang sama dengan create juga dimuat dan view edit dipanggil.

### `store(Request $request)` dan `update(Request $request, Itinerary $itinerary)`

Keduanya punya pola yang hampir sama:

- validasi field utama itinerary
- validasi semua input per-day
- validasi schedule items
- normalisasi `dayPoints`
- normalisasi `daily transport units`
- bersihkan field array yang tidak langsung disimpan ke tabel utama itinerary
- sync semua relasi:
  - tourist attractions pivot
  - itinerary activities
  - itinerary food beverages
  - day points
  - itinerary transport units

Pada create:

- `created_by` diisi user login
- `status` dipaksa `draft`

Pada create/update:

- `destination_id` di-resolve dari text destination
- inquiry yang masih `draft` bisa dipindahkan ke `processed`

## 3. Persiapan Data Di `_form.blade.php`

Bagian paling atas `_form.blade.php` adalah lapisan normalisasi data untuk Blade.

Yang dilakukan di sini:

- menentukan default `buttonLabel`
- menentukan `$selectedInquiryId`
- menentukan `$durationDays` dan `$durationNights`
- membaca `old(...)` bila validasi gagal
- jika tidak ada old input, ambil data dari `$itinerary`

Data schedule dibentuk dari tiga sumber terpisah:

- `itinerary_items` untuk attraction
- `itinerary_activity_items` untuk activity
- `itinerary_food_beverage_items` untuk F&B

Ketiganya diubah menjadi struktur gabungan `$rows`, lalu:

- diberi `item_type`
- diberi `day_number`
- diberi `visit_order`
- diberi `_sort`
- dikelompokkan menjadi `$rowsByDay`

Data per-day lain yang juga dipersiapkan:

- `dailyStartPointTypes`
- `dailyStartPointItems`
- `dailyStartPointRoomIds`
- `dailyEndPointTypes`
- `dailyEndPointItems`
- `dailyEndPointRoomIds`
- `dailyTransportUnitItems`
- `dailyMainExperienceTypes`
- `dailyMainExperienceItems`
- `dayIncludes`
- `dayExcludes`

Selain itu dibuat juga `inquiryPreviewMap`, yaitu payload ringkas untuk sidebar inquiry detail.

## 4. Struktur Form Yang Dirender

Bagian form utama berisi:

- inquiry select
- title
- destination autocomplete
- duration days
- duration nights
- description
- hidden container `hotel-stays-hidden`

Lalu bagian terbesar adalah `#day-sections`.

Setiap `day-section` memiliki:

- header day
- start tour time
- auto end tour time
- tombol add attraction/activity/F&B
- transport unit harian
- day start point
- day start point disusun satu baris untuk pasangan selector type (`Airport/Hotel`) + item
- day start room bila start point hotel
- travel minutes dari start point ke item pertama
- kumpulan `schedule-row`
- day end point
- day end point disusun satu baris untuk pasangan selector type (`Airport/Hotel`) + item
- end-time text indicator di pojok kanan atas card day end point (sinkron dengan end time kalkulasi)
- day end room bila end point hotel
- hidden main experience type/item
- day includes
- day excludes
- frontend required rules untuk field wajib (termasuk conditional required untuk point item/room berdasarkan type)
- indikator visual wajib (`*` merah) otomatis tampil pada label field yang required (termasuk required dinamis)

## 5. Struktur `schedule-row`

Setiap row schedule mewakili satu item itinerary di satu hari.

Komponennya:

- drag handle
- badge urutan item
- item type selector
- region selector (`city`) per row
- select attraction
- select activity
- select F&B
- hidden pax
- start time readonly
- end time readonly
- main experience checkbox
- tombol remove
- hidden travel minutes to next
- hidden day number
- hidden visit order

Format label option pada selector item:
- Attraction: tampil `Attraction name`
- Activity: tampil `Activity name - Vendor name`
- F&B: tampil `F&B name - Vendor name`

Row tidak langsung punya `name` untuk semua input. `name` baru dipasang ulang oleh JavaScript saat `reindex()` sesuai tipe item dan hanya untuk row yang dianggap aktif/terpilih.

## 6. Sidebar Inquiry Detail

Sidebar inquiry detail sepenuhnya client-side.

Alurnya:

- `inquiryPreviewMap` di-embed dari Blade ke JS
- saat inquiry select berubah, fungsi `setDetail()` dijalankan
- card `Inquiry Detail` disembunyikan jika inquiry belum dipilih
- field sidebar diisi dari map tersebut
- bila inquiry kosong, card detail tidak ditampilkan

Artinya sidebar inquiry tidak fetch ulang ke server saat user memilih inquiry.

## 7. Destination Autocomplete Dan Filter

Field destination punya dua fungsi:

- suggestion dropdown dari endpoint `itineraries.destination-suggestions`
- filter lokal terhadap option yang punya atribut `data-city` / `data-province`

Flow-nya:

- input destination mengetik -> debounce -> fetch suggestions
- item dropdown dipilih -> value input diisi
- perubahan destination memicu filter select:
  - item attraction
  - item activity
  - item F&B
  - start point item
  - end point item
  - transport unit
- perubahan region pada row schedule memicu filter lokal row tersebut:
  - item attraction
  - item activity
  - item F&B

Ada juga `MutationObserver` supaya filter destination dijalankan ulang saat DOM berubah, misalnya ketika day/row baru diclone.

## 8. Peran Fungsi JavaScript Utama

### `getRowSelection(row)`

Fungsi ini menentukan select aktif suatu row berdasarkan:

- nilai `.item-type`
- `data-item-type`
- value aktual pada select attraction/activity/F&B

Fungsi ini menjadi basis:

- `rowType(row)`
- `activeSelect(row)`
- `selected(row)`

### `toggleType(row, type, reset = true)`

Mengubah row ke mode attraction/activity/F&B:

- show/hide select yang relevan
- reset value select lain bila perlu
- update `data-item-type`

### `rebuildTravelConnectors(section)`

Membangun ulang UI connector travel antar item.

Travel antar item sebenarnya disimpan pada hidden input `.item-travel`, tetapi ditampilkan sebagai card connector terpisah di antara row.
UI connector disederhanakan menjadi input setengah lebar, tanpa label terpisah (menggunakan placeholder), dengan ikon mobil di sisi kiri dalam input.
Untuk menghindari overlap ikon dengan placeholder/text, input menggunakan wrapper left-affix dengan `padding-left` khusus.

### `recalcDay(section)`

Ini inti kalkulasi waktu harian.

Yang dilakukan:

- ambil semua row yang dianggap selected
- sinkronkan main experience
- hitung label start/end point
- reset row yang kosong
- isi badge urutan item
- hitung start/end time tiap row berdasarkan:
  - day start time
  - day start travel minutes
  - duration item dari `data-duration`
  - travel minutes ke item berikutnya
- hitung `day-end-time`

### `recalcAll()`

Menjalankan `recalcDay()` untuk semua hari berurutan.

### `reindex()`

Fungsi ini membentuk payload submit aktual.

Tugasnya:

- reset semua `name`
- isi `day_number` ke hidden input row
- hanya row selected yang diberi `name`
- mapping name dibedakan per tipe:
  - attraction -> `itinerary_items[...]`
  - activity -> `itinerary_activity_items[...]`
  - F&B -> `itinerary_food_beverage_items[...]`

Ini penting: submit backend sepenuhnya bergantung pada hasil `reindex()`.

### `syncDayPointOptionRules()`

Tugasnya:

- merapikan nomor day saat jumlah hari berubah
- merapikan text label per-day
- merapikan `name` semua field per-day
- memastikan day 1 tidak punya option `previous_day_end`
- memastikan day > 1 punya option `previous_day_end`
- memastikan end point bisa punya option airport

### `syncPointItemVisibility()`

Tugasnya:

- filter option start/end point berdasarkan type terpilih
- filter option berdasarkan destination
- enable/disable room select
- sembunyikan room yang tidak cocok dengan hotel yang dipilih

### `syncMainExperienceSelection()`

Memastikan hanya satu row per day yang menjadi main experience.

Main experience disimpan ke hidden field day-level:

- `daily_main_experience_types[day]`
- `daily_main_experience_items[day]`

### `getDayStartPoint(day, previousEndPoint)` dan `getDayEndPoint(day)`

Fungsi ini membaca titik start/end point untuk keperluan map.

Khusus start point:

- bila type = `previous_day_end`, maka titik diambil dari end point hari sebelumnya

### `buildHotelStaysPayload()` dan `syncHotelStaysHidden()`

Fitur ini menyusun hidden payload ringkas untuk stay hotel dari end point per day.

Payload dihasilkan dari pengelompokan hari berurutan dengan hotel yang sama:

- `hotel_id`
- `day_number`
- `night_count`
- `room_count`

### `renderMap()`

Fungsi ini membangun map preview.

Alur besarnya:

- clear dynamic map layers
- bangun anchor start/end per day
- baca row schedule yang valid
- kelompokkan point per day
- gabungkan start point + schedule point + end point
- render marker dengan badge bernomor
- untuk tiap day:
  - bila point >= 2, gambar fallback polyline
  - lalu coba ambil route nyata dari OSRM
  - bila OSRM sukses, fallback diganti route jalan
  - render badge travel minutes di tengah segmen

Saat ini `renderMap()` juga memuat debug instrumentation:

- total days
- schedule rows
- parsed points
- renderable points
- marker layers
- active route layers
- debug detail per row
- debug anchor dan hasil OSRM

### `bindRow(row)` dan `cloneRow(section, type)`

`bindRow()` mendaftarkan listener pada row:

- type change
- item select change
- main experience toggle
- remove row

`cloneRow()` menggandakan template row pertama lalu me-reset isinya.

### `initSortable(section)`

Mengaktifkan drag-and-drop reorder antar row, bahkan antar hari, melalui SortableJS.

Setelah drag selesai, `recalc()` dipanggil.

### Handler perubahan durasi

Saat `duration_days` berubah:

- section hari yang kurang akan dibuat dengan `cloneNode`
- section yang lebih akan dihapus
- semua field day-level direset untuk day baru
- satu row template dipertahankan pada day baru
- listener semua elemen baru dipasang ulang

### Submit handler

Sebelum submit:

- `recalcAll()`
- `reindex()`
- `syncAccommodationStaysHidden()`
- validasi minimal 1 attraction
- validasi end point wajib diisi di semua hari
- jika lolos, `form.submit()`

## 9. Alur Payload Ke Backend

Saat submit, controller menerima payload dari tiga lapisan:

### Layer itinerary utama

- title
- destination
- inquiry_id
- duration_days
- duration_nights
- description
- is_active

### Layer schedule item

- `itinerary_items`
- `itinerary_activity_items`
- `itinerary_food_beverage_items`

### Layer day-level

- `daily_start_point_types`
- `daily_start_point_items`
- `daily_start_point_room_ids`
- `day_start_times`
- `day_start_travel_minutes`
- `day_includes`
- `day_excludes`
- `daily_end_point_types`
- `daily_end_point_items`
- `daily_end_point_room_ids`
- `daily_main_experience_types`
- `daily_main_experience_items`
- `daily_transport_units`

Controller lalu:

- memvalidasi semua array tersebut
- memanggil `normalizeDayPoints()`
- memanggil `normalizeDailyTransportUnits()`
- melakukan sync ke relasi itinerary

## 10. Aturan Bisnis Yang Terlihat Jelas Dari Kode

- itinerary create/update minimal harus punya 1 attraction
- duration nights tidak boleh melebihi duration days
- end point wajib diisi untuk setiap day
- main experience harus berasal dari item yang memang ada di hari yang sama
- day number item tidak boleh melebihi duration itinerary
- itinerary final tidak boleh diedit/dihapus
- itinerary yang quotation-nya approved tidak boleh diedit
- status inquiry draft dapat diubah ke processed saat itinerary tersimpan

## 11. Konsistensi Domain Yang Penting

Selama audit, area paling penting untuk dipahami adalah bahwa create/edit itinerary sekarang harus dibaca sepenuhnya dengan domain `hotel`.

### A. Start/end point harian

Start dan end point harian disimpan dengan pasangan field:

- `start_point_type` / `end_point_type`
- `start_hotel_id` / `end_hotel_id`
- `start_airport_id` / `end_airport_id`
- `start_hotel_room_id` / `end_hotel_room_id`

Nilai `type` yang valid untuk point ini:

- `previous_day_end`
- `hotel`
- `airport`

### B. Sumber data view dan controller selaras

Controller create/edit mengirim:

- `$hotels`
- `$airports`
- `$transportUnits`

`_form.blade.php` juga membaca source yang sama, sehingga select day point dan room hotel bergerak dengan bahasa domain yang konsisten.

### C. Hidden payload stay hotel

Ringkasan stay hotel dikirim lewat hidden input:

- `hotel_stays[...][hotel_id]`
- `hotel_stays[...][day_number]`
- `hotel_stays[...][night_count]`
- `hotel_stays[...][room_count]`

Payload ini disusun dari end point per day yang bertipe `hotel`.

### D. Validasi transport unit tampak mengarah ke tabel yang salah

Controller memuat data dari `TransportUnit`, tetapi validasi field submit memakai:

- `exists:transports,id`

Padahal field yang dikirim oleh form adalah `transport_unit_id`.

Ini sangat patut dicurigai sebagai mismatch model/tabel.

### E. Ada duplikasi blok validasi hotel di `normalizeDayPoints()`

Dalam `normalizeDayPoints()` terdapat blok validasi start hotel dan end hotel yang terulang dua kali.

Itu bukan mengubah flow utama, tetapi menunjukkan area ini pernah diubah dan belum dirapikan.

## 12. Referensi Map Detail Itinerary (Show Page)

Dokumen ini fokus pada create/edit flow.  
Untuk implementasi map pada halaman detail itinerary (`show`) gunakan referensi khusus:

- `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`

Kenapa dipisah:
- Create/edit map adalah preview berbasis state DOM form.
- Show map adalah render berbasis data itinerary tersimpan.
- Logika polyline show map sudah diset untuk route jalan saja (tanpa fallback garis lurus).

## 13. Gambaran Mental Sistem Create/Edit

Cara paling tepat membayangkan sistem ini adalah:

- Controller menyiapkan semua master data dan state itinerary lama
- `_form.blade.php` mengubah data lama itu menjadi state per-day yang bisa dirender
- JavaScript menganggap form sebagai editor dinamis berbasis DOM, bukan state store formal
- Waktu, visit order, payload name, main experience, stay summary, dan map semuanya dibangun ulang dari DOM setiap kali user mengubah sesuatu
- Submit backend tidak membaca "row visual", tetapi membaca field yang sudah dinamai ulang oleh `reindex()`
- Day point dan transport unit adalah lapisan data terpisah dari schedule item
- Map hanyalah refleksi dari state DOM saat itu, bukan source of truth

Source of truth yang sesungguhnya saat create/edit adalah kombinasi:

- DOM row yang sedang aktif
- hidden input per row
- hidden input per day
- hasil `reindex()`
- hasil `normalizeDayPoints()` di backend

Catatan UI terbaru untuk point hotel:
- Saat type point = `hotel`, susunan input dibuat satu baris: `Type + Item + Room` (baik Start Point maupun End Point).
- Implementasi layout row menggunakan grup flex responsif agar stabil di tablet/desktop dan tetap aman di mobile.

## 14. Kesimpulan Praktis

Halaman create/edit itinerary saat ini adalah form dinamis yang sangat padat, dengan tiga lapisan logika:

- Blade prefill dan grouping data
- JavaScript runtime untuk mengelola state DOM
- Controller normalization untuk menyimpan ke struktur relasional

Jika ada bug di halaman ini, biasanya akar masalahnya jatuh ke salah satu dari tiga kategori:

- DOM state tidak sinkron dengan apa yang terlihat user
- naming field berubah saat `reindex()` / clone day / clone row
- relasi antara state DOM, hidden payload, dan normalisasi controller belum sinkron

Dokumen ini sengaja ditulis sebagai peta navigasi sebelum melakukan perbaikan lebih lanjut pada halaman create/edit itinerary.

## 15. Responsive Hardening (Mobile/Tablet)

Per 2026-03-30, itinerary views (`create`, `edit`, `_form`, `show`, `index`) ditambah guard untuk mencegah konten terpotong di layar kecil:

- Wrapper grid/card/aside itinerary diberi `min-w-0` agar child bisa shrink dan tidak memaksa overflow.
- Header day card di `_form` tidak lagi memakai `min-w-[280px]` pada pill endpoint summary.
- Meta text `Starts at / Ends at` pada day header sekarang boleh wrap.
- Baris `Start Tour / End Tour` sekarang mobile-safe:
  - group bisa wrap,
  - label tetap singkat (`whitespace-nowrap`),
  - input time pakai lebar responsif (`w-full sm:w-36`).
- Group tombol `Add Attraction / Add Activity / Add F&B` sekarang boleh wrap di mobile.
- Fallback CSS mobile (`max-width: 640px`) menumpuk day header secara vertikal dan memaksa `travel-connector` full width.

Tujuan perubahan ini: memastikan seluruh form itinerary tetap utuh (tidak terpotong kanan/kiri) pada perangkat mobile dan tablet tanpa mengubah perilaku bisnis/payload.
