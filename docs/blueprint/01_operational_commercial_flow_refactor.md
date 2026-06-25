# VOYEX CRM Operational Commercial Flow Refactor

Last Updated: 2026-06-25
Status: In Progress

## Target Workflow
1. Inquiry
2. Quotation
3. Customer/Agent Approval
4. Booking
5. Vendor Confirmation per Item
6. Voucher per Item
7. Service Operation
8. Actual Service Reconciliation
9. Final Invoice
10. Payment / Settlement
11. Booking Closed

## Architecture Mapping
- Inquiry: customer/agent request intake.
- Itinerary: travel planning engine.
- Quotation: commercial agreement and pricing baseline.
- Booking: operational execution container after approval.
- Booking Item: operational snapshot from quotation item.
- Voucher: vendor/provider proof for each booking item.
- Adjustment: operational change log and commercial impact.
- Invoice: billing document based on actual service.
- Payment: payment receipt and allocation tracking.
- Settlement: closing and profit reconciliation.

## Mandatory Business Rules
- Booking can only be created from approved quotation.
- Draft quotation remains editable before approval.
- Post-approval changes must be recorded as revision/adjustment trail.
- Quotation/booking item cancellation must store charge mode (percent/fixed) and amount.
- Voucher is generated per item after approval and vendor confirmation.
- Vendor unavailable/fully booked must support replace/cancel with audit trail.
- Final invoice must use actual used services + additions + cancellation charges + adjustments + discount/refund.
- Early invoice can be issued before service date as proforma/draft/issued-temporary and remain revisable.

## Current Baseline (Detected in Repository)
- `quotation_approvals` and quotation lifecycle exist.
- `bookings` + `booking_items` + voucher table exist.
- `booking_adjustments` + settlement + payment tables exist.
- Invoice lifecycle hardening migration exists.

## Implementation Plan (Safe Incremental)

### Phase A - Workflow Guardrails
- [ ] Enforce booking creation gate strictly on quotation approved status in service/controller layer.
- [ ] Add explicit rejection response and activity log when conversion attempted from non-approved quotation.
- [ ] Add feature test for approved vs non-approved conversion.

### Phase B - Post-Approval Revision Integrity
- [ ] Freeze original approved quotation version metadata.
- [ ] Store post-approval change request as revision record (new table, do not mutate legacy migration).
- [ ] Link revision entries to actor, reason, before/after diff snapshot.
- [ ] Add UI indicator: "Approved with revisions".

### Phase C - Booking Item Operational Reality
- [ ] Add per-item vendor confirmation status lifecycle (pending/confirmed/unavailable/replaced/cancelled).
- [ ] Store unavailable reason and replacement link (`replaced_by_booking_item_id`).
- [ ] Ensure cancellation charge data is persisted and reflected in adjustments.

### Phase D - Voucher Discipline
- [ ] Enforce voucher generation only for eligible item states.
- [ ] Keep immutable voucher snapshot fields for vendor name, service date, and sell/buy reference.
- [ ] Add item-level voucher timeline in booking detail.

### Phase E - Actual Service Reconciliation
- [ ] Add reconciliation layer to mark item as used/partially used/no-show/cancelled.
- [ ] Support additional ad-hoc items (upsell/emergency replacement) with audit metadata.
- [ ] Compute invoiceable lines from reconciled data source.

### Phase F - Invoice Staging to Final
- [ ] Distinguish invoice purpose: proforma/draft/issued/final/cancelled.
- [ ] Generate early invoice from voucher-ready items with provisional flag.
- [ ] Add controlled revise flow before finalization.
- [ ] Lock final invoice once settlement window starts.

### Phase G - Payment and Settlement Closure
- [ ] Ensure payment allocation to invoice(s) is traceable.
- [ ] Settlement calculation includes realized revenue/cost/adjustment/cancellation/refund.
- [ ] Block booking close if invoice/payment/settlement prerequisites are incomplete.

## Data Safety Rules
- Use additive migrations only.
- Do not edit previous migrations.
- Keep backward-compatible defaults for nullable or status columns.
- Backfill with idempotent migration scripts where needed.
- Add tests before lifecycle-hardening changes when possible.

## Execution Checklist
- [x] Customer index now uses the official index baseline: KPI/Summary Cards, compact one-row filter card, data table/mobile cards, and no dedicated index sidebar.
- [ ] Phase A implementation merged.
- [ ] Phase B implementation merged.
- [ ] Phase C implementation merged.
- [ ] Phase D implementation merged.
- [ ] Phase E implementation merged.
- [ ] Phase F implementation merged.
- [ ] Phase G implementation merged.
- [ ] UAT and production rollout checklist updated.
