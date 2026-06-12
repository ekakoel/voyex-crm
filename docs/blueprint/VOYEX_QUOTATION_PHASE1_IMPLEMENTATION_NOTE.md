# VOYEX Quotation Refactor - Phase 1 Implementation Note

## Scope Checked

- Models: `Quotation`, `QuotationItem`, `Inquiry`, `Itinerary`, `Booking`, `Invoice`, and related payment flow.
- Controllers: quotation, quotation validation, inquiry, booking, booking reconciliation/settlement, invoice, and payment controllers.
- Views: `resources/views/modules/quotations/*`, quotation PDF views, and quotation comments partial.
- Routes: `routes/web.php`, `routes/api.php`.
- Database: quotation, quotation item, approval/comment/validation, booking, booking item, invoice, payment, and permission migrations/seeders.

## Current Status Fields Found

- `quotations.status`: currently normalized in model to:
  `draft`, `pending_validation`, `validated`, `sent`, `customer_approved`, `booking_created`, `in_operation`, `completed`, `cancelled`, `lost`.
  Legacy map exists: `accepted -> customer_approved`, `converted -> booking_created`, `valid -> validated`, `rejected -> lost`, `final -> completed`.
- `quotations.validation_status`: `pending`, `partial`, `valid`.
- `quotation_items.status`: `active`, `validated`, `vendor_pending`, `vendor_confirmed`, `voucher_generated`, `used`, `cancelled_free`, `cancelled_with_charge`, `not_available`, `replaced`, `added_after_approval`.
- `inquiries.status`: includes `new_request`, `assigned`, `itinerary_in_progress`, `quotation_in_progress`, `quotation_sent`, `accepted`, `converted_to_booking`, `lost`, `cancelled`, `expired`.
- `itineraries.status`: `draft`, `quotation_generated`, `converted_to_booking`, plus draft/approved style values in options.
- `bookings.status`: includes `created`, `vendor_confirmation`, `pending_confirmation`, `confirmed`, `awaiting_dp`, `ready_to_operate`, `in_operation`, `service_completed`, `reconciliation`, `invoiced`, `completed_settled`, `closed`, `cancelled`.
- `invoices.status`: `draft`, `issued`, `partially_paid`, `paid`, `overpaid`, `revised`, `void`, `cancelled`.
- `payments.status`: `pending`, `waiting_confirmation`, `confirmed`, `rejected`, `cancelled`, `refunded`, `allocated_as_deposit`.

Missing from the README as physical quotation columns: `send_status`, `approval_status`, `booking_status`, `invoice_status`, `payment_status`, `operation_status`, `current_stage`, `next_action`, `handled_by`, `last_sent_at`, `cancelled_at`, `completed_at`.

## Current Routes Found

- Quotation resource routes: `quotations.index/create/store/show/edit/update/destroy`.
- Quotation extras:
  `quotations.my`, `quotations.toggle-status`, `quotations.itinerary-items`, `quotations.pdf`, `quotations.export`.
- Workflow/action routes:
  `quotations.mark-sent`, `quotations.mark-customer-approved`, `quotations.approve`, `quotations.reject`, `quotations.set-pending`, `quotations.set-final`, `quotations.global-discount`.
- Validation routes:
  `quotations.validate.show`, `save-progress`, `save-item`, `item-detail-json`, `update-item-contact`, `validate-selected`, `finalize`.
- Comment routes:
  `quotations.comments.store/update/destroy`.
- Related downstream routes exist for bookings, booking operation, reconciliation, settlement, invoice lifecycle, final/proforma invoice generation, and payments.
- `routes/api.php` only exposes the default authenticated `/user` route; no quotation API workflow exists.

## Current Quotation Item Structure

- `quotation_items` supports polymorphic service linkage via `serviceable_type` and `serviceable_id`.
- Pricing fields exist: `contract_rate`, `markup_type`, `markup`, `unit_price`, `discount_type`, `discount`, `total`.
- Itinerary/day context exists: `day_number`, `service_date`, `serviceable_meta`, `itinerary_item_type`.
- Validation fields exist: `is_validation_required`, `is_validated`, `validated_at`, `validated_by`, `validation_notes`, `last_validated_contract_rate`, `last_validated_markup_type`, `last_validated_markup`.
- Operational/cancellation fields exist: `status`, `cancellation_fee_type`, `cancellation_fee_value`, `cancellation_fee_amount`, `cancellation_reason`, `actual_used_at`, `replaced_by_item_id`.
- Detailed validation logs exist through `quotation_item_validations`; service validation logs also exist through `service_item_validations`.

## Current Relation Structure

- `Quotation` belongs to `Inquiry` and `Itinerary`; has one `Booking`; has many `QuotationItem`, `QuotationComment`, `QuotationApproval`, activities, and self-revisions through `revision_of_id`.
- `Inquiry` belongs to `Customer`, `handledBy`, and `assignedTo`; has many quotations and a latest quotation alias.
- `Itinerary` belongs to inquiry and creator; has many quotations and inquiry references.
- `Booking` belongs to quotation; has many invoices, items, adjustments; has one latest invoice and settlement.
- `Invoice` belongs to booking; has many payments and confirmed payments.
- `Payment` belongs to invoice and tracks creator/confirmer/rejector.

## Gaps Against Required Workflow

- Quotation still relies primarily on one `status` plus `validation_status`; other workflow dimensions are derived, not persisted.
- Required statuses such as `ready_to_send`, `under_revision`, `pending_revalidation`, `approved`, `booking_in_progress`, `booking_issue`, `invoiced`, `waiting_payment`, `pending`, `operation_adjustment`, and `finalized` are not first-class quotation statuses.
- Revision support exists on `quotations` but not as a separate `quotation_revisions` table with requested-by/requested-at metadata.
- Status logs such as `quotation_status_logs`, `quotation_send_logs`, and dedicated `quotation_approval_logs` are not present.
- `handled_by`, `next_action`, and `current_stage` are not quotation columns; they are inferred from inquiry/user/status context.
- Booking, invoice, payment, and operation states are modeled downstream but not synced back into separate quotation status dimensions.
- UI now has better derived visibility on detail, but action visibility and workflow task ownership still need a formal service layer.
- Some destructive legacy migrations exist historically; future migrations must be additive and backward compatible only.

## Safest Implementation Path

1. Keep existing `quotations.status` and `validation_status` as backward-compatible source fields.
2. Add nullable quotation workflow dimension columns in one additive migration:
   `send_status`, `approval_status`, `booking_status`, `invoice_status`, `payment_status`, `operation_status`, `current_stage`, `next_action`, `handled_by`, `last_sent_at`, `cancelled_at`, `completed_at`.
3. Backfill those fields from existing status, validation, booking, invoice, payment, and operation data with a safe command or migration guard.
4. Introduce a `QuotationWorkflowService` that computes and persists dimensions through controlled transitions.
5. Add append-only workflow log tables before changing transition behavior.
6. Move action button visibility into a single workflow presenter/service so Blade does not duplicate business rules.
7. Keep PDF, calculation, create/edit/detail, and validation pages working while gradually routing status mutations through the service.
8. Add focused tests around transition guards: validation before send, sent to approval/lost/cancelled, approved to booking, booking issue to revision/revalidation, invoice/payment/operation completion.

