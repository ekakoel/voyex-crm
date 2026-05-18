# Booking Module Technical Guide

Last Updated: 2026-05-18

## 1. Scope
Module Booking mencakup:
- create/edit/show booking,
- service item booking workspace,
- booking log,
- voucher preview/PDF,
- service item cancellation.

Flow utama:
`Quotation (approved + validation complete) -> Booking -> Book Service Item -> Voucher -> Cancel (if needed)`

## 2. Data Model Overview
Tabel utama:
- `bookings`
- `booking_items`
- `booking_item_booking_logs`
- `booking_item_vouchers`

Untuk cancellation fee policy:
- `service_cancellation_policies` (header policy per serviceable)
- `service_cancellation_policy_rules` (rule detail: min/max day, fee type, fee value, description)

## 3. Service Item Naming Standard (Booking UI)
Semua nama service item pada UI Booking wajib menyertakan provider context:
- Service berbasis vendor/provider: `service name | vendor/provider name`
- Service hotel: `service name | hotel name`

Tujuan:
- menghindari ambigu saat nama service serupa,
- mempercepat operator memilih item yang benar.

## 4. Cancellation Policy vs Cancellation Fee (Separated Concern)
Pemisahan wajib:
- `Cancellation Policy` (text/rich text): referensi manusia.
- `Cancellation Fee Rules` (structured): engine/prefill fee.

Field structured rule:
- `min_days_before`
- `max_days_before`
- `fee_type` (`fixed`/`percent`)
- `fee_value`
- `description`

## 5. Cancel Service Item Behavior (Current)
Saat cancel service item:
1. Modal menampilkan cancellation policy text bila tersedia.
2. `Cancellation Fee Type` + `Cancellation Fee` diprefill dari rules (snapshot/fallback policy).
3. User tetap boleh adjust manual.
4. Fee disimpan ke booking item sesuai type:
   - `percent` dihitung dari total item,
   - `nominal` mengikuti currency aktif UI lalu disimpan canonical ke IDR.
5. Jika service item belum punya default fee rules, input cancel user disimpan menjadi default policy rules service item terkait.
6. Jika policy text service item kosong, user dapat input policy text langsung di modal cancel, lalu disimpan ke service item terkait.

## 6. Hotel-Specific Rule
Untuk service type `HotelRoom`:
- cancellation fee policy target menggunakan level `Hotel` (bukan per room),
- policy text referensi menggunakan gabungan:
  - `cancellation_policy`
  - `cancellation_policy_traditional`
  - `cancellation_policy_simplified`

## 7. Multi-language Standard in Booking
Semua text user-facing pada module Booking wajib `ui_phrase(...)`.
Larangan:
- hardcoded label/button/modal text/flash message.

Minimal area yang wajib i18n:
- form create/edit,
- service workspace modal (book/edit/cancel),
- voucher preview/modal,
- flash notification controller.

## 8. Multi-currency Standard in Booking
Aturan:
- Input nominal di UI mengikuti currency aktif user.
- Persistensi database canonical menggunakan IDR.
- Tampilan nominal output gunakan formatter standar (`App\Support\Currency::format` / money component).

Implikasi create/edit booking item:
- `unit_price` input dari UI harus dikonversi ke IDR sebelum save.

## 9. Performance Baseline (Booking)
Optimasi aktif:
- eager loading morph `serviceable` + provider relation di create/edit booking,
- fallback policy query dipindah dari Blade ke Controller,
- fallback policy hanya dihitung untuk item tanpa snapshot rules,
- detail booking tidak lagi load full `bookingLogs` collection jika cukup `latestBookingLog`.

Rule lanjutan:
- hindari query DB di Blade loop,
- gunakan precomputed map di controller untuk data turunan berat.

## 10. QA Checklist (Booking)
Setiap perubahan Booking wajib cek:
1. Create booking dari quotation eligible.
2. Edit booking + booking services modal (book/edit/cancel).
3. Voucher preview modal + PDF.
4. Cancel flow (policy ada/tidak ada).
5. Multi-language phrase tampil benar.
6. Multi-currency nominal tampil dan tersimpan benar.
7. Tidak ada query N+1 baru pada list item booking.

## 11. Notes/Reason Deprecation (2026-05-18)
Booking module tidak lagi memakai field `Notes / Reason`.

Area yang dihapus:
1. Booking form:
- field `bookings.notes`.
- field per-item `booking_items.notes`.
2. Booking services workspace:
- field `notes` di modal `Book Service Item`.
- field `notes` di modal `Edit Booking Service`.
- field `cancellation_notes` di modal `Cancel Item`.
- kolom `Notes` pada tabel services.
3. Booking detail/show:
- kolom `Notes` pada tabel services.
- payload `voucher notes` pada trigger preview modal.
4. Voucher form:
- field `booking_item_vouchers.notes`.

Database cleanup:
1. Migration: `2026_05_18_235900_remove_notes_reason_fields_from_booking_module_tables.php`.
2. Kolom yang di-drop:
- `bookings.notes`
- `booking_items.notes`
- `booking_items.cancellation_notes`
- `booking_item_booking_logs.notes`
- `booking_item_vouchers.notes`
