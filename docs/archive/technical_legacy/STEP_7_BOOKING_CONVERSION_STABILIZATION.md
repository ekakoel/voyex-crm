# STEP 7 - Booking Conversion Stabilization

Last Updated: 2026-05-20

## Scope
- Fokus pada alur convert Quotation -> Booking.
- Tidak menambah modul Payment/Adjustment/Settlement.
- Tidak ubah migration lama.

## What Was Inspected
- `app/Http/Controllers/BookingController.php`
- `app/Http/Requests/StoreBookingRequest.php`
- `app/Http/Requests/UpdateBookingRequest.php`
- `app/Models/Booking.php`
- `routes/web.php`

## Fixes Applied
1. Quotation eligibility untuk create/edit booking diperketat:
   - hanya quotation status `accepted` yang dapat dipilih untuk booking conversion.
2. Validasi request booking diselaraskan:
   - `StoreBookingRequest` dan `UpdateBookingRequest` sekarang menolak quotation selain `accepted`.
3. Stabilitas data ditingkatkan dengan transaksi DB:
   - create booking (`store`) sekarang transactional untuk:
     - create booking header,
     - sync booking items,
     - generate invoice.
   - update booking (`update`) sekarang transactional untuk:
     - update booking header,
     - sync booking items (jika tidak locked),
     - regenerate invoice.
4. Header action booking ditambahkan:
   - `cancel booking` endpoint + guard:
     - tidak bisa cancel jika booking final,
     - tidak bisa cancel jika invoice sudah ada jejak pembayaran (`partially_paid/paid/overpaid`).
   - `close booking` endpoint + settlement guard:
     - hanya bisa close jika status service sudah completed (`service_completed/completed_settled`),
     - invoice sudah tersedia,
     - invoice sudah settled (`paid/overpaid`),
     - tidak semua service item berstatus cancelled.
5. UI index booking ditambahkan tombol:
   - `Close Booking`
   - `Cancel Booking`
6. Participant/pax strategy ditutup:
   - booking sekarang menyimpan `pax_adult` dan `pax_child` sebagai snapshot level header.
   - nilai pax dikirim dari form booking dan divalidasi (`pax_adult + pax_child > 0`).
7. Dedicated itinerary snapshot ditutup:
   - booking sekarang menyimpan `itinerary_snapshot` (JSON) dari quotation->itinerary saat create/update.
   - disiapkan migration backfill untuk data booking lama agar tetap kompatibel.

## Business Rules Covered
1. Convert to booking hanya dari quotation accepted.
2. Booking tetap menyimpan `quotation_id`.
3. Booking items tetap terbentuk dari item quotation terpilih.
4. Kegagalan di tengah proses create/update tidak meninggalkan data parsial.
5. Cancel booking action tersedia di level header.
6. Close booking diproteksi oleh settlement guard.

## How To Test
1. Buka create booking.
2. Pastikan dropdown quotation hanya menampilkan status `accepted`.
3. Coba submit manual dengan quotation status selain `accepted`:
   - harus ditolak validasi.
4. Buat booking normal dari quotation accepted:
   - booking tersimpan,
   - booking items tersimpan,
   - invoice generation tetap berjalan.
5. Update booking existing:
   - perubahan header/items/invoice tetap konsisten (atomik).
6. Coba klik `Cancel Booking`:
   - booking final harus ditolak,
   - booking dengan invoice berstatus ada pembayaran harus ditolak,
   - booking eligible harus berubah ke status `cancelled`.
7. Coba klik `Close Booking`:
   - harus ditolak jika service belum selesai / invoice belum settled,
   - harus sukses jika semua guard settlement terpenuhi.
8. Jalankan migration baru:
   - pastikan kolom `bookings.pax_adult`, `bookings.pax_child`, dan `bookings.itinerary_snapshot` terisi.
   - cek booking existing tetap terbaca (hasil backfill dari quotation/itinerary).

## Remaining Gaps (Phase 7)
1. Tidak ada gap kritikal tersisa untuk scope Phase 7 saat ini.
