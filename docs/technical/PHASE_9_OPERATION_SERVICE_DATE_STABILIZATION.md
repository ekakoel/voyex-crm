# PHASE 9 - Operation / Service Date Stabilization

Date: 2026-05-20

## Scope

Fase ini menstabilkan alur operasional booking tanpa menyentuh Adjustment, Settlement, dan Closing final.

## Current Architecture Findings

1. Sistem sudah memiliki `booking.status` sebagai sumber status utama, belum ada kolom operation status terpisah.
2. Service list operasional bersumber dari `booking_items` + `latestBookingLog` (service date, contact, confirmation).
3. Booking close masih sengaja diblokir sampai fase settlement.
4. Payment + invoice lifecycle sudah tersedia dari Phase 8A/8B.

## Implemented in Phase 9

1. Operation lifecycle actions ditambahkan:
- `markReadyToOperate`
- `startOperation`
- `completeService`
- `reportOperationIssue`

2. Route operation baru:
- `bookings.operation.ready`
- `bookings.operation.start`
- `bookings.operation.complete`
- `bookings.operation.issue`

3. Permission operation baru:
- `bookings.operation.view`
- `bookings.operation.prepare`
- `bookings.operation.start`
- `bookings.operation.complete`
- `bookings.operation.issue`

4. Payment guard untuk masuk `ready_to_operate`:
- Booking harus punya minimal 1 invoice dengan kondisi:
  - status invoice `paid`/`overpaid`, atau
  - minimal 1 payment `confirmed`.

5. Activity log ditambahkan untuk:
- booking marked ready_to_operate
- operation started
- service completed
- operation issue reported

6. Booking show UI:
- tombol aksi operation berbasis status + permission
- ringkasan operation (status, invoice count, confirmed payment count, eligibility)
- form lapor issue operasional

7. Auto status hardening:
- booking create default status menjadi `confirmed` (bukan auto-loncat ke operation state berdasarkan tanggal).
- booking update tidak menimpa status operation yang sudah berjalan.

## Business Rules Covered

1. Booking masuk operation hanya jika status + payment guard valid.
2. Operation progression jelas: `confirmed/awaiting_*` -> `ready_to_operate` -> `in_operation` -> `service_completed`.
3. Booking tidak bisa closed pada fase ini (tetap diblokir).
4. Perubahan operasional tidak mengubah invoice/payment langsung.
5. Semua action operation diproteksi permission dan terekam activity log.

## Deferred (Not in this phase)

- Financial correction during operation (Adjustment phase)
- Settlement gating and final booking close
- Deposit/refund ledger accounting detail
