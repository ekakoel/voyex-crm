# Nominal Input Standard

Last Updated: 2026-04-17


## Tujuan

Standarisasi semua input nominal (price/rate/amount/fee/cost/discount/total) agar:
- konsisten lintas modul,
- aman saat submit,
- mudah dipakai user (format ribuan terbaca),
- tidak drift antara halaman lama dan baru.

## Wajib Dipakai

1. Gunakan komponen `x-money-input` untuk semua field nominal di Blade.
2. Badge nominal wajib di sisi kiri (`left affix`).
3. Field nominal tidak menggunakan `<input type="number">` plain untuk UI akhir.

## Perilaku Format

1. Tampilan input memakai format ribuan `id-ID` (contoh `2.880.000`).
2. Sebelum submit, nilai dinormalisasi menjadi angka murni (contoh `2880000`).
3. Untuk field markup:
   - `markup_type=percent` -> badge `%`,
   - `markup_type=fixed` -> badge symbol/code currency aktif (`Rp`, `$`, dll).

## Currency-Aware Rule

1. Gunakan `window.appCurrency`, `window.appCurrencySymbol`, dan metadata currency global jika halaman mendukung multi-currency display.
2. Jika UI menampilkan nilai non-IDR, payload backend tetap harus mengikuti kontrak modul (normalisasi numerik yang disepakati service/controller).

## Implementasi Referensi

- Komponen: `resources/views/components/money-input.blade.php`
- Utility CSS affix: `resources/css/app.css` (`.input-with-left-affix`, `.input-left-affix`)
- Normalisasi global submit: `resources/views/layouts/master.blade.php` (`attachMoneyHints`, `normalizeMoneyInputsBeforeSubmit`)
- Contoh halaman kompleks: `resources/views/modules/quotations/validate.blade.php`

## Aturan Untuk Halaman Baru

1. Dilarang membuat pola nominal baru yang berbeda dari komponen standar.
2. Jika ada kebutuhan khusus, extend `x-money-input` atau util global, bukan bypass standar.
3. Perubahan standar wajib update:
   - `PROJECT_GUIDELINES.md`,
   - `VOYEX_CRM_AI_GUIDELINE.md`,
   - `VOYEX_CRM_SYSTEM_ROADMAP.md` (changelog).
