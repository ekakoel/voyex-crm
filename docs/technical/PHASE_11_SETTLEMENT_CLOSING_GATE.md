# Phase 11 - Settlement & Closing Gate

Date: 2026-05-21

## Scope

Phase ini hanya mengaktifkan settlement review dan closing gate booking.
Tidak mencakup WhatsApp SPK sharing, driver/guide master module, atau accounting journal.

## Delivered

1. Tabel baru `booking_settlements` via migration baru:
- `database/migrations/2026_05_21_000000_create_booking_settlements_table.php`
- field checklist, nominal summary, metadata blockers, review/finalize audit.

2. Model baru `App\Models\BookingSettlement`:
- status options settlement,
- relasi `booking`, `reviewer`, `finalizer`,
- helper: `isSettled`, `isBlocked`, `canReview`, `canFinalize`, `hasOutstanding`, `hasOverpayment`.

3. Service baru `App\Services\SettlementService`:
- `generateSettlementNumber`
- `reviewBooking`
- `calculateSettlementSummary`
- `detectBlockingIssues`
- `markSettled`
- `closeBooking`
- `lockBookingAfterClose`

4. Controller baru `App\Http\Controllers\BookingSettlementController`:
- `show`
- `review`
- `markSettled`
- `close`

5. Route settlement baru:
- `GET /bookings/{booking}/settlement`
- `POST /bookings/{booking}/settlement/review`
- `POST /bookings/{booking}/settlement/mark-settled`
- `POST /bookings/{booking}/settlement/close`

6. Booking detail integration:
- Settlement Summary card
- Settlement Review quick button
- Close Booking button muncul hanya jika status settlement `settled` dan user memiliki permission.

7. Settlement page UI:
- ringkasan booking,
- checklist pass/blocked per rule,
- summary invoice/payment/outstanding/overpaid,
- blocker list,
- notes + action buttons sesuai permission.

8. Permission additions:
- `booking_settlements.view`
- `booking_settlements.review`
- `booking_settlements.mark_settled`
- `booking_settlements.close_booking`

9. Close booking guard:
- direct route lama `bookings.close` tetap tidak melakukan close langsung, sekarang redirect ke settlement review.
- status `closed` ditetapkan hanya dari `SettlementService::lockBookingAfterClose()`.

10. Activity log events:
- `settlement.reviewed`
- `settlement.blocked`
- `settlement.marked_settled`
- `booking.close_rejected`
- `booking.closed`

## Settlement Blocking Rules Implemented

1. Service belum `service_completed` -> block.
2. Outstanding invoice (`total_invoice_amount - total_paid_amount > 0`) -> block.
3. Ada payment `pending`/`waiting_confirmation` -> block.
4. Ada adjustment `draft`/`pending_approval`/`approved` (belum applied) -> block.
5. Overpayment (`total_paid_amount > total_invoice_amount`) -> block sampai diselesaikan (ledger deposit/refund automation masih tahap berikutnya).

## Notes

- Overpayment resolution ledger otomatis (deposit/refund posting) masih `PARTIAL`; sekarang status blocker sudah ada dan menahan close.
- Booking lock setelah close mengikuti guard existing (`isFinal`) sehingga edit/delete/cancel unsafe diblok.
