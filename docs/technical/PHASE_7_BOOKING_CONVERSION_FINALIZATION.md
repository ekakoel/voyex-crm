# PHASE 7 Booking Conversion Finalization

Last Updated: 2026-05-20

## Scope
- Finalisasi Phase 7 saja.
- Tidak implement Payment, Adjustment, Settlement.
- Tidak refactor modul di luar booking conversion.

## What Was Inspected
- `app/Http/Controllers/BookingController.php`
- `app/Http/Requests/StoreBookingRequest.php`
- `app/Http/Requests/UpdateBookingRequest.php`
- `app/Models/Booking.php`
- `app/Models/BookingItem.php`
- `app/Services/BookingSnapshotService.php`
- `database/migrations/*bookings*`
- `resources/views/modules/bookings/*.blade.php`
- `routes/web.php`

## Participant / Pax Strategy
1. Total pax direpresentasikan oleh:
   - `bookings.pax_adult`
   - `bookings.pax_child`
2. Rule saat create/update booking:
   - `pax_adult` required integer >= 0
   - `pax_child` required integer >= 0
   - `pax_adult + pax_child > 0`
3. Sumber nilai pax:
   - default copy dari quotation (`pax_adult/pax_child`)
   - disimpan sebagai snapshot booking agar tidak ikut berubah saat quotation berubah.
4. Participant detail (nama per peserta):
   - belum diwajibkan di Phase 7.
   - dapat ditambahkan di phase operation/payment tanpa memblokir conversion.
5. Infant:
   - belum dipisah sebagai field dedicated.
   - dipertahankan sebagai gap terkontrol untuk fase berikutnya bila dibutuhkan bisnis.

## Itinerary Snapshot Strategy
1. Snapshot itinerary disimpan di booking:
   - `bookings.itinerary_snapshot` (JSON).
2. Isi snapshot:
   - `id`, `title`, `destination_id`, `destination_name`, `duration_days`, `duration_nights`, `snapshot_at`.
3. Snapshot service operasional:
   - `booking_items` menyimpan copy service data penting (`description`, `qty`, `unit_price`, `total`, `serviceable_type`, `serviceable_id`, `day_number`, `serviceable_meta`).
4. Relasi referensial tetap dipertahankan:
   - `bookings.quotation_id` tetap ada.
   - snapshot digunakan sebagai source stabil untuk tampilan/operasi agar perubahan itinerary/quotation setelah conversion tidak silently mengubah booking.

## What Was Changed
1. Status eligibility booking conversion diselaraskan:
   - boleh dari quotation `accepted` atau `converted` (final quotation status).
2. Guard phase rule ditegakkan:
   - action `close booking` dinonaktifkan di Phase 7 (hard guard di controller + tombol close di list dihilangkan).
3. Validasi pax diperjelas di FormRequest:
   - required + non-negative + total pax tidak boleh 0.
4. Snapshot booking dipakai konsisten:
   - `pax_adult/pax_child` + `itinerary_snapshot` diisi saat store/update.
5. I18n dictionary ditambah untuk pesan baru yang dipakai guard/validation.

## Business Rules Coverage
1. Booking hanya dari quotation accepted/final sesuai status standar: covered.
2. Snapshot quotation/itinerary saat conversion: covered.
3. Snapshot service untuk operasi: covered via `booking_items`.
4. Tidak bergantung penuh pada data mutable quotation/itinerary setelah conversion: covered.
5. Pax jelas dan konsisten: covered.
6. Participant rule eksplisit: not required at creation (deferred).
7. Booking tidak bisa closed di phase ini: covered (hard-block).
8. Struktur siap untuk phase lanjutan: covered baseline.

## How To Test
1. Jalankan migration terbaru.
2. Buat booking dari quotation `accepted` dan `converted`:
   - harus bisa.
3. Coba create/update dengan pax 0/0:
   - harus ditolak.
4. Cek tabel `bookings`:
   - `pax_adult`, `pax_child`, `itinerary_snapshot` terisi.
5. Ubah data itinerary/quotation setelah booking dibuat:
   - detail pax/destination/itinerary di booking tetap memakai snapshot.
6. Coba akses action close booking:
   - harus ditolak dengan pesan phase guard.

## Remaining Risks
1. Participant detail per traveler belum menjadi entitas dedicated.
2. Infant pax field belum terpisah.
3. Close-by-settlement final rule tetap harus diaktifkan pada Phase 10.

