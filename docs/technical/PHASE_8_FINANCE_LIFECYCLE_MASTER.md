# Phase 8 Finance Lifecycle Master

Last Updated: 2026-05-20

## Scope
- Konsolidasi Phase 8A + 8B + 8B-QA:
  - invoice lifecycle hardening,
  - payment module foundation,
  - payment QA stabilization.

## 1) Invoice Lifecycle Hardening (8A)
- Multi-invoice per booking enabled.
- Billing fields normalized:
  - `invoice_type`, `subtotal`, `discount_amount`, `tax_amount`,
  - `total_amount`, `paid_amount`, `balance_amount`.
- Invoice helpers:
  - `isDraft()`, `isIssued()`, `isPaid()`, `isEditable()`, `recalculateBalance()`.
- Lifecycle actions:
  - update invoice, issue, void, cancel.
- Guard:
  - paid/overpaid/void/cancelled invoice blocked from unsafe edit.

## 2) Payment Module Foundation (8B)
- New `payments` schema (non-destructive).
- Payment lifecycle statuses:
  - `pending`, `waiting_confirmation`, `confirmed`, `rejected`, `cancelled`.
- Payment service transactional:
  - create/confirm/reject/cancel + invoice recalculation.
- Accounting rule:
  - only `confirmed` payments affect `invoice.paid_amount`.
- Invoice status recalculation:
  - `partially_paid`, `paid`, `overpaid` based on paid vs total.
- Permissions:
  - `payments.view/create/confirm/reject/cancel`.

## 3) Payment QA Stabilization (8B-QA)
- Permission boundaries verified:
  - Reservation not default confirmer/rejector.
- Lifecycle guard verification:
  - confirmed/rejected/cancelled payment cannot be re-confirmed improperly.
- Invoice receive-payment guard:
  - `void/cancelled/draft` invoice cannot receive payment.
- Proof upload hardening:
  - validated file type + size.
- Automated tests:
  - `tests/Feature/Finance/PaymentServiceTest.php`.

## 4) Current Business Coverage
1. Invoice lifecycle safe edit and status transition.
2. Payment posting flow with explicit approval state.
3. Invoice paid/balance recalculation consistency.
4. Overpayment state recognized (`overpaid`).
5. Activity log for payment critical actions.

## 5) Deferred Items
1. Deposit ledger / overpayment allocation.
2. Refund accounting detail across invoices.
3. Settlement orchestration final closing gate integration.
