# Quotation Validation UAT Matrix

Last Updated: 2026-04-16

Dokumen ini untuk validasi fitur Quotation Validation end-to-end sebelum approval.

## Preconditions
1. Jalankan migration terbaru:
   - `php artisan migrate`
2. User role yang disiapkan:
   - `Marketing` (creator quotation)
   - `Reservation`
   - `Manager`
   - `Director`
3. Quotation memiliki minimal 1 item validasi wajib:
   - `Hotel` dengan kondisi `Hotel arranged by us`, atau
   - `Activity` / `Food & Beverage` / `Transport` / `Tourist Attraction`.

## Scenario Matrix

| No | Actor | Action | Expected Result |
|---|---|---|---|
| 1 | Marketing | Buka quotation detail | Tombol `Validate Quotation` tidak tampil (bukan role validator). |
| 2 | Reservation/Manager/Director | Buka quotation detail | Tombol `Validate Quotation` tampil jika quotation belum valid dan ada item wajib validasi. |
| 3 | Reservation/Manager/Director | Buka halaman `/quotations/{id}/validate` | Halaman validasi terbuka dengan ringkasan progress. |
| 4 | Reservation/Manager/Director | Klik kolom `Vendor/Provider/Item` di halaman validasi | Modal detail terbuka dan memuat data via endpoint JSON (lazy load), bukan preload semua item. |
| 5 | Reservation/Manager/Director | Ubah `contract_rate`, `markup_type`, `markup` item lalu `Save Item` | Quotation item tersimpan, audit validation tercatat, source master rate ikut terupdate. |
| 6 | Reservation/Manager/Director | Centang validasi pada sebagian item lalu `Save Progress` | Progress tersimpan, `validation_status` quotation menjadi `partial`. |
| 7 | Reservation/Manager/Director | Klik `Validate` (per row save item) | Item tersimpan via AJAX tanpa reload halaman, checkbox validated otomatis tercentang jika data valid. |
| 8 | Reservation/Manager/Director | Cek tombol `Validate Quotation` saat masih ada item belum tervalidasi | Tombol tidak tampil sampai semua item wajib validasi selesai. |
| 9 | Reservation/Manager/Director | Klik `Validate Quotation` saat semua item wajib tervalidasi | `validation_status` quotation menjadi `valid`, `validated_at/by` terisi. |
| 10 | Reservation/Manager/Director | Coba approve quotation sebelum `validation_status=valid` | Ditolak dengan pesan: `Quotation cannot be approved because validation is not completed.` |
| 11 | Reservation/Manager/Director | Approve quotation setelah `validation_status=valid` | Approval flow berjalan normal sesuai quorum role existing. |
| 12 | Manager/Director/Reservation | Cek update source rate untuk item non-hotel | Master data source (Activity/F&B/Transport/Attraction) ikut berubah sesuai rate terbaru. |
| 13 | Manager/Director/Reservation | Cek update source rate untuk Hotel | Sistem membuat record `HotelPrice` baru (versioning) dengan `start_date=today`, `end_date=+1 month`. |
| 14 | Manager/Director/Reservation | Cek audit trail | Perubahan old->new rate tercatat di `quotation_item_validations` dan `service_rate_histories`. |
| 15 | Reservation/Manager/Director | Buka modal detail item | Section contact menampilkan nama provider + address (read-only), field update hanya contact person/phone/email/website via AJAX. |
| 16 | Reservation/Manager/Director | Lihat title modal item | Format title tampil `Day N - Item Type - Item Name` dengan label readable (`Food and Beverage`, `Tourist Attraction`, `Transport`). |

## Negative Test (Recommended)
1. Role `Marketing` akses endpoint validasi:
   - `GET /quotations/{id}/validate`
   - `GET /quotations/{id}/validate/items/{item}/detail-json`
   Expected: `403 Forbidden`.
2. Set `markup_type=percent` dan `markup > 100`.
   Expected: validation error.
3. Item hotel dengan mode `self booked`.
   Expected: item bukan mandatory validation.

## Performance Checkpoint
1. Halaman validasi quotation besar (banyak item) tetap responsif pada first load.
2. Detail modal item menggunakan lazy API JSON per klik kolom `Vendor/Provider/Item`.
3. Tidak ada N+1 query berat saat render list utama validasi.

## Exit Criteria
1. Semua scenario matrix pass.
2. Guard approval sebelum validasi selesai terbukti bekerja.
3. Audit trail terbentuk untuk setiap perubahan validation rate.
4. Tidak ada regression pada flow approval existing.
