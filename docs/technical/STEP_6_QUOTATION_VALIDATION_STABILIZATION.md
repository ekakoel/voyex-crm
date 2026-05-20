# STEP 6 — Quotation Validation Stabilization

Last Updated: 2026-05-20

## Scope
- Fokus hanya pada stabilisasi flow **Quotation Validation**.
- Tidak menambah modul baru.
- Tidak mengubah migration lama.

## What Was Inspected
- `routes/web.php`
- `app/Http/Controllers/Sales/QuotationValidationController.php`
- `app/Http/Controllers/Sales/QuotationController.php`
- `app/Services/QuotationValidationService.php`
- `app/Policies/QuotationPolicy.php`
- `resources/views/modules/quotations/show.blade.php`
- `resources/views/modules/quotations/validate.blade.php`
- `docs/blueprint/07_MODULE_IMPLEMENTATION_CHECKLIST.md`
- `docs/blueprint/04_ROADMAP_CHECKLIST.md`

## Current Stabilization Result
1. Dedicated validation page dan route tersedia:
   - `quotations.validate.show`
   - `quotations.validate.save-progress`
   - `quotations.validate.save-item`
   - `quotations.validate.item-detail-json`
   - `quotations.validate.update-item-contact`
   - `quotations.validate.validate-selected`
   - `quotations.validate.finalize`
2. Validation item modal tersedia dan lazy-load detail item/contact berjalan.
3. Contract rate + markup type/value dapat diperbarui sesuai permission dan flow validasi.
4. Bulk validate tersedia (`validateSelected`).
5. Finalize validation tersedia (`finalize`).
6. `validated_by` dan `validated_at` tersimpan via service.
7. Validation log tersedia via item history/log payload.
8. Guard lock sudah aktif untuk quotation berstatus:
   - `sent`
   - `accepted`
   - `converted`
   sehingga perubahan validasi tidak bisa dilakukan di status locked.
9. Approval guard aktif:
   - Quotation tidak bisa naik ke `accepted` bila validasi belum complete (`canBeApproved` check).

## Critical Note
- Route/status transition khusus `sent` belum dijadikan aksi terpisah yang diekspos di UI.
- Secara praktik saat ini, gate utama sebelum approval adalah validasi lengkap.
- Jika ke depan fitur "mark as sent" diaktifkan, wajib pakai guard yang sama dengan approval (`canBeApproved`) agar konsisten.

## How To Test
1. Buka detail quotation dengan item validasi wajib.
2. Pastikan tombol `Validate Quotation` muncul hanya untuk user berpermission.
3. Buka halaman `/quotations/{id}/validate`.
4. Ubah `contract_rate/markup`, lalu klik validate per item.
5. Jalankan bulk validate untuk item terpilih.
6. Jalankan finalize.
7. Pastikan:
   - progress berubah,
   - `validation_status` berubah,
   - validator info (`validated_by/validated_at`) terisi.
8. Coba approve sebelum validasi complete:
   - harus ditolak.
9. Ubah status quotation ke `accepted/converted`, lalu coba akses update validasi:
   - harus terkunci.

## Remaining Risk
- Karena aksi `sent` belum dibuka sebagai flow terpisah di UI, guard “prevent sent if not validated” saat ini efektif lewat gate approval, bukan lewat endpoint sent khusus.
