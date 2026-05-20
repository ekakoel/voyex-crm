# Phase 10 - Adjustment / Amendment Module

Date: 2026-05-20

## Scope
Phase 10 implements Booking Adjustment/Amendment workflow only.
Settlement and Closing remain deferred.

## Implemented
1. New table `booking_adjustments` with lifecycle, approval, and apply metadata.
2. New model `App\\Models\\BookingAdjustment` with:
- adjustment types,
- impact types,
- status helpers and lifecycle guards.
3. New service `App\\Services\\AdjustmentService` with methods:
- `createDraft`, `updateDraft`, `submitForApproval`, `approve`, `reject`, `cancel`, `apply`,
- `applyFinancialImpact`,
- `generateAdjustmentNumber`.
4. Financial impact rules (transactional):
- `charge` => create `additional_charge` invoice,
- `cancellation_fee` => create `cancellation_fee` invoice,
- `refund` / `credit` => create `refund` invoice,
- `non_financial` => no invoice mutation.
5. New controller `BookingAdjustmentController` and lifecycle endpoints.
6. New form requests:
- `StoreBookingAdjustmentRequest`,
- `UpdateBookingAdjustmentRequest`,
- `BookingAdjustmentLifecycleRequest`.
7. New routes:
- `/bookings/{booking}/adjustments`
- `/bookings/{booking}/adjustments/create`
- `/booking-adjustments/{adjustment}`
- lifecycle: submit/approve/reject/apply/cancel.
8. Booking detail now includes Adjustment Summary card + quick links.
9. Permission scaffolding added:
- `booking_adjustments.view/create/update/submit/approve/reject/apply/cancel`.
10. Activity logs added for adjustment lifecycle and generated invoice event.

## Guard Rules
1. Only draft can be edited/submitted.
2. Only pending approval can be approved/rejected.
3. Only approved can be applied.
4. Applied adjustment is locked.
5. Linked booking item/invoice/payment must belong to same booking.
6. Adjustment creation does not mutate existing invoice/payment directly.
7. Existing paid invoices and confirmed payments are not mutated.

## Manual Test
1. Open Booking Detail -> click `Create Adjustment`.
2. Create `additional_service` + `charge` adjustment.
3. Submit -> approve -> apply.
4. Confirm additional invoice generated.
5. Create `manual_adjustment` + `non_financial`.
6. Submit -> approve -> apply.
7. Confirm no invoice/payment mutation.
8. Create `refund` adjustment and apply.
9. Confirm `refund` invoice generated (no payment refund processing yet).
10. Reject one adjustment and confirm no financial impact.

## Deferred to Next Phases
1. Settlement review gate.
2. Booking close enablement.
3. Deposit ledger / full refund accounting journal.
