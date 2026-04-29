# Itinerary Detail Map Architecture

Last Updated: 2026-04-29


Dokumen ini menjelaskan implementasi map pada halaman detail itinerary (`show`) agar developer/AI bisa memahami sistem tanpa trial-error.

Referensi kode utama:
- `resources/views/modules/itineraries/show.blade.php`

## 1. Tujuan Fitur

Map di halaman detail itinerary harus:
- menampilkan marker untuk setiap titik itinerary (start, item schedule, end),
- mendukung filter `All Days` atau per hari,
- menampilkan polyline per segmen sesuai tipe perjalanan:
  - route darat: OSRM driving,
  - route laut transfer antar pulau: `route_geojson` dari master island transfer,
- tidak menampilkan polyline lurus sebagai fallback.

## 2. Komponen UI

Di `show.blade.php`:
- kontainer map: `#itinerary-show-map`
- tombol filter hari: `.itinerary-day-filter-btn`
  - `data-day=""` untuk `All Days`
  - `data-day="N"` untuk `Day N`
- card map sisi kanan memakai `h-fit lg:self-start xl:sticky xl:top-6` agar:
  - tinggi card mengikuti konten map,
  - card tetap terlihat/fixed saat user scroll ke bawah (desktop `xl`),
  - card kembali ke posisi awal saat user scroll kembali ke atas.

## 3. Sumber Data Titik (Server-side / PHP)

Data map dibangun dulu di Blade (PHP) sebelum masuk JavaScript:

1. Ambil `dayPoints` itinerary dan index berdasarkan `day_number`.
2. Fungsi `$resolveMapPoint(...)` menentukan start/end point:
   - start bisa `previous_day_end`, `airport`, `hotel`
   - end bisa `airport`, `hotel`
3. Loop hari `1..duration_days`, lalu bentuk koleksi `$mapPoints` berisi:
   - anchor start day (jika ada koordinat valid),
   - semua attraction hari itu (dari `$dayGroups`),
   - semua activity hari itu (vendor lat/lng),
   - semua island transfer hari itu (departure + arrival point),
   - semua F&B hari itu (vendor lat/lng),
   - anchor end day (jika ada koordinat valid).

Struktur item `$mapPoints`:
- `type` (`attraction|activity|transfer|fnb|hotel|airport`)
- `name`
- `location`
- `lat`, `lng`
- `day_number`
- `visit_order`
- `map_order` (internal ordering helper for render sequence)
- `transfer_role` + `transfer_pair_key` (khusus transfer departure/arrival pairing)
- `route_to_next_coords` (opsional, khusus transfer departure bila tersedia `route_geojson`)

Semua titik di-sort sebelum dikirim ke JS:
- urutan utama: `day_number`
- urutan kedua: `visit_order`

## 4. Inisialisasi Leaflet (Client-side / JS)

IIFE map menggunakan strategi "safe boot":

1. `initializeMap()` hanya jalan jika:
   - elemen `#itinerary-show-map` ada,
   - `window.L` tersedia.
2. Jika `window.L` belum ada:
   - `bootWhenReady()` retry berkala.
   - setelah beberapa percobaan, `requestLeafletFallback()` memuat Leaflet CDN sekali.
3. Map dibuat dengan mode stabil:
   - `preferCanvas: false`
   - `renderer: L.svg()`

Alasan:
- menghindari error Canvas klasik seperti `Cannot read properties of undefined (reading 'x')`.

## 5. Normalisasi Data Titik

Sebelum render:
- validasi `lat/lng` harus finite dan dalam range wajar,
- validasi `day_number > 0`,
- normalisasi `type` ke enum yang dikenali.

Titik invalid dibuang sebelum marker/polyline diproses.

## 6. Render Marker

Fungsi `renderMarkers(day)`:
- membersihkan layer map (`mapDataLayer.clearLayers()`),
- memilih titik aktif:
  - semua hari (jika `day === null`),
  - atau hanya hari tertentu,
- membuat marker bernomor per hari dengan ikon berdasarkan `type`,
- bind popup marker berisi nomor, day, nama, lokasi.

## 7. Render Polyline Route Segment

Implementasi polyline:

1. Titik dikelompokkan per hari.
2. Untuk setiap hari dengan minimal 2 titik:
   - proses pasangan titik berurutan (`A->B`, `B->C`, dst).
   - jika titik asal memiliki `route_to_next_coords`, gunakan route custom tersebut.
   - jika tidak ada route custom, fallback ke route OSRM driving per segmen.
   - gabungkan seluruh segmen menjadi satu route harian.
3. Hanya jika route jalan valid (`>= 2` titik), polyline digambar.
4. Jika salah satu segmen gagal resolve, polyline hari tersebut di-skip.

Penting:
- tidak ada fallback polyline lurus.
- hasil akhir adalah satu jalur yang mengikuti mode perjalanan sebenarnya (darat/laut).

Endpoint route:
- `https://router.project-osrm.org/route/v1/driving/{lng,lat;...}?overview=full&geometries=geojson`

## 8. Kontrol Concurrency dan Stabilitas Render

Untuk mencegah race condition:
- `routeRenderToken` dipakai untuk membatalkan hasil render lama,
- `AbortController` membatalkan fetch route sebelumnya saat render baru dimulai,
- guard `mapBusy` + `renderPendingAfterMove` menunda render saat zoom/pan aktif,
- `requestSafeRender(...)` memastikan ukuran kontainer map sudah valid sebelum render.

## 9. Perilaku Tombol Hari

Saat klik `.itinerary-day-filter-btn`:
- `selectedDay` diupdate,
- gaya tombol aktif/nonaktif diubah (`btn-primary-sm` vs `btn-outline-sm`),
- map dirender ulang sesuai filter.

`All Days` berarti:
- marker semua hari tampil,
- polyline route tiap hari tampil (warna per hari berdasarkan palette).

## 10. Kenapa Pernah Gagal Sebelumnya (dan Solusi)

Kasus yang pernah terjadi:
- `toLatLng is not defined`: helper belum tersedia saat dipanggil.
- `reading 'x'` pada Leaflet Canvas/Renderer:
  - map/layer dirender saat state belum siap,
  - atau data layer mengandung titik invalid,
  - atau timing render bertabrakan saat map bergerak.

Perbaikan yang dipertahankan:
- helper konsisten dalam scope inisialisasi,
- semua titik divalidasi sebelum `L.marker`/`L.polyline`,
- gunakan renderer SVG (bukan canvas mode),
- gunakan render guard dan token pembatalan.

## 11. Aturan Ubah Kode (Untuk AI/Developer Selanjutnya)

Jika mengubah map detail itinerary:
- jangan kembalikan fallback garis lurus, kecuali ada kebutuhan bisnis baru,
- pertahankan pola render segment-by-segment dan prioritas `route_to_next_coords` untuk transfer,
- pertahankan guard render (`routeRenderToken`, `AbortController`, `mapBusy`),
- pastikan `type` marker tetap sinkron dengan data `mapPoints` dari server,
- setelah perubahan, selalu cek:
  - map muncul,
  - marker tampil,
  - polyline mengikuti jalur yang benar (darat/laut),
  - filter day berfungsi,
  - tidak ada error console.

## 12. Checklist QA Cepat

1. Buka halaman detail itinerary dengan data multi-day.
2. Pastikan marker semua item muncul saat `All Days`.
3. Pastikan segmen transfer antar pulau mengikuti `route_geojson` (contoh Sanur -> Nusa Penida langsung).
4. Klik `Day 1`, `Day 2`, dst dan cek marker + route terfilter benar.
5. Cek console browser: tidak ada error JS Leaflet.
