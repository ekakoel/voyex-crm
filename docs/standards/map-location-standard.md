# Map & Location Standard

Last Updated: 2026-06-11

Dokumen ini menjadi standar tunggal untuk semua module yang memakai Google Maps URL, koordinat, reverse geocoding, dan interactive map picker.

## Tujuan
- Menyamakan UX lokasi lintas modul.
- Mengurangi duplikasi script map per modul.
- Menjamin data `latitude`, `longitude`, `address`, `city`, `province`, `country`, `location`, dan `timezone` konsisten antara UI dan backend.

## Komponen Wajib
- `components.map-standard-section`
- `components.google-maps-autofill-row`
- `components.location-coordinate-row`
- `components.location-map-picker`

## Kontrak Form
- Root form wajib memakai:
  - `data-location-autofill`
  - `data-location-resolve-url="{{ route('location.resolve-google-map') }}"`
- URL input wajib memakai `x-google-maps-autofill-row`.
- Koordinat wajib memakai `x-location-coordinate-row`.
- Jika modul memiliki interactive map, gunakan `components.location-map-picker` melalui partial modul.

## Kontrak Backend
- Controller create/update wajib tetap memanggil `LocationResolver` sebelum simpan.
- Minimal field yang disiapkan untuk enrich:
  - `google_maps_url`
  - `location`
  - `city`
  - `province`
  - `country`
  - `address`
  - `latitude`
  - `longitude`
  - `timezone`
- Frontend autofill membantu UX, tetapi validasi dan enrich final tetap server-side.

## Perilaku Wajib
- Full URL Google Maps dan short link harus didukung.
- `Auto Fill` harus mengisi field lokasi yang tersedia.
- Saat `latitude` atau `longitude` berubah, pin map harus ikut berpindah.
- Saat user klik map atau drag pin:
  - `latitude` dan `longitude` harus ikut berubah,
  - map URL harus ikut diperbarui,
  - data yang tersimpan ke database tetap berasal dari field final hasil sinkronisasi.

## Icon Mapping Standard
- `Destinations`: `fa-map-location-dot`
- `Vendors / Providers`: `fa-store`
- `Hotels`: `fa-hotel`
- `Airports`: `fa-plane-departure`
- `Tourist Attractions`: `fa-camera-retro`
- `Company Settings`: `fa-building`

## Modul Yang Sudah Mengikuti Standar
- `Destinations`
- `Vendors / Providers`
- `Hotels`
- `Airports`
- `Tourist Attractions`
- `Company Settings`

## Catatan Implementasi
- Jika sebuah modul memakai nama field map yang berbeda, misalnya `map`, gunakan:
  - `mapFieldName`
  - `mapFieldErrorKey`
  - `field` pada `x-google-maps-autofill-row`
- Jika modul tidak membutuhkan field destination, set:
  - `showDestinationField => false`
- Semua label user-facing harus memakai `ui_phrase()`.
