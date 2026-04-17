# Quotation Validation UAT Matrix

Last Updated: 2026-04-17

Dokumen ini untuk validasi fitur Quotation Validation end-to-end sebelum approval.

## Preconditions
1. Jalankan migration terbaru:
   - `php artisan migrate`
2. Sync permission role terbaru:
   - `php artisan db:seed --class=PermissionSeeder`
   - `php artisan db:seed --class=RolePermissionSeeder`
3. User validator memiliki permission:
   - `quotations.validate`
4. Quotation memiliki item validasi wajib:
   - Hotel (hanya jika `Hotel arranged by us`),
   - Activity,
   - Food & Beverage,
   - Transport,
   - Tourist Attraction.

## Scenario Matrix

| No | Actor | Action | Expected Result |
|---|---|---|---|
| 1 | Non-validator | Buka quotation detail | Tombol `Validate Quotation` tidak tampil |
| 2 | Validator | Buka quotation detail | Tombol `Validate Quotation` tampil jika quotation belum approved/final |
| 3 | Validator | Buka `/quotations/{id}/validate` | Halaman validasi terbuka + KPI progress muncul |
| 4 | Validator | Klik kolom `Vendor/Provider/Item` | Modal detail item load via endpoint JSON (lazy load) |
| 5 | Validator | Ubah `contract_rate`, `markup_type`, `markup` lalu klik `Validate` | Save per-row via AJAX tanpa reload, checkbox validated otomatis aktif |
| 6 | Validator | Klik `Save Progress` | Perubahan tersimpan, status quotation menjadi `pending/partial` sesuai progress |
| 7 | Validator | Cek KPI saat validate item | `Total Validated Items` dan progress persen update realtime |
| 8 | Validator | Cek tombol `Validate Quotation` saat progress < 100% | Tombol tidak tampil |
| 9 | Validator | Cek tombol saat progress 100% | Tombol `Validate Quotation` tampil |
| 10 | Validator | Klik `Validate Quotation` | `validation_status` menjadi `valid`, `validated_at/by` terisi |
| 11 | Approver | Coba approve sebelum `validation_status=valid` | Ditolak dengan pesan validation guard |
| 12 | Validator | Revalidation sebelum status approved/final | Halaman validasi tetap bisa diakses untuk koreksi |
| 13 | Validator | Cek sync ke module source saat validasi disimpan | Data source module + quotation item ikut terupdate |
| 14 | User modul source | Ubah rate di module source (Activity/F&B/Transport/Attraction/Hotel) | Data di halaman validation mengikuti source terbaru |
| 15 | Validator | Cek modal contact detail | Contact person/phone/email/website update via AJAX, address tampil read-only |

## Negative Test (Recommended)
1. User tanpa `quotations.validate` akses endpoint validasi.
   Expected: `403 Forbidden`.
2. Simpan item dengan data nominal invalid.
   Expected: validation error.
3. Item hotel mode `self booked` muncul sebagai mandatory validation.
   Expected: tidak mandatory.

## Performance Checkpoint
1. Halaman validasi quotation besar tetap responsif pada first load.
2. Detail modal item lazy-load per item.
3. Tidak ada N+1 query berat saat render list utama validasi.

## Exit Criteria
1. Semua scenario matrix pass.
2. Guard approval sebelum validasi selesai terbukti bekerja.
3. Audit trail perubahan validation rate terbentuk.
4. Tidak ada regression pada flow quotation approval.
