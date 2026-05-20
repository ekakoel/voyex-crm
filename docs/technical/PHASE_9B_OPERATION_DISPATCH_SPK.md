# PHASE 9B - Operation Dispatch & SPK Stabilization

Date: 2026-05-20

## Scope

Fase ini fokus pada stabilisasi dispatch operasional dan SPK untuk booking yang sudah `ready_to_operate`.

Tidak termasuk:
- Adjustment
- Settlement
- Closing
- Refund/deposit ledger

## Dispatch Strategy

1. Tetap gunakan `booking_items` sebagai operational service list.
2. Tetap gunakan `bookings.status` sebagai lifecycle operasi utama.
3. Karena belum ada master module Driver/Guide dedicated, assignment driver/guide disimpan sebagai field teks nullable di `booking_items`.

## Schema Added

Migration baru:
- `2026_05_20_234000_add_operation_dispatch_fields_to_booking_items_table.php`

Field dispatch:
- `vendor_confirmation_status`
- `vendor_confirmed_at`
- `vendor_confirmed_by`
- `assigned_driver_name`
- `assigned_driver_phone`
- `assigned_guide_name`
- `assigned_guide_phone`
- `operation_notes`
- `dispatch_status`
- `issue_note`

## Actions Added

Booking-level:
- `showSpk()`

Booking-item dispatch:
- `confirmItemVendor()`
- `updateItemDispatch()`
- `markItemDispatchReady()`
- `markItemDispatchCompleted()`
- `reportItemDispatchIssue()`

## Routes Added

- `bookings.spk`
- `bookings.items.vendor-confirm`
- `bookings.items.dispatch.update`
- `bookings.items.dispatch.ready`
- `bookings.items.dispatch.complete`
- `bookings.items.dispatch.issue`

## Permissions Added

- `bookings.operation.dispatch`
- `bookings.operation.vendor_confirm`
- `bookings.operation.assign_driver`
- `bookings.operation.assign_guide`
- `bookings.operation.spk.view`
- `bookings.operation.spk.print`

Default role assignment:
- Enabled for: Reservation, Manager, Director, Administrator, Super Admin
- Not enabled by default: Finance, Accountant

## SPK Structure

SPK printable page (`resources/views/modules/bookings/spk.blade.php`) mencakup:
- Booking summary (number, status, customer, service date, pax, itinerary)
- Operational service items
- Vendor confirmation status
- Dispatch status
- Driver/guide assignment
- Operation notes / issue note

SPK tidak mengubah quotation/invoice/payment.

## Activity Logs

Dicatat:
- `operation.spk_viewed`
- `operation.item_vendor_confirmed`
- `operation.item_dispatch_updated`
- `operation.item_driver_assigned`
- `operation.item_guide_assigned`
- `operation.item_ready`
- `operation.item_completed`
- `operation.item_issue_reported`

## Remaining Limits

1. Driver/guide masih assignment teks (belum master data entity).
2. Vendor confirmation checklist belum berbasis workflow multi-step detail.
3. WhatsApp sharing SPK/schedule belum diimplementasikan.
