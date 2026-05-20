# PHASE 8A - Invoice Lifecycle Hardening

Last Updated: 2026-05-20

## Scope
- Fokus hanya hardening lifecycle invoice.
- Tidak implement Payment module penuh.
- Tidak implement Adjustment/Settlement/Closing.

## What Was Hardened
1. **Schema readiness for billing lifecycle**
   - Multi-invoice per booking enabled (drop unique `booking_id` constraint).
   - Added/normalized billing fields:
     - `invoice_type`
     - `subtotal`
     - `discount_amount`
     - `tax_amount`
     - `total_amount`
     - `paid_amount`
     - `balance_amount`
2. **Invoice model lifecycle helpers**
   - `isDraft()`
   - `isIssued()`
   - `isPaid()`
   - `isEditable()`
   - `recalculateBalance()`
3. **Centralized amount calculation**
   - `InvoiceService::computeAmounts(...)`
4. **Lifecycle actions + guards**
   - Edit/update invoice billing fields
   - Issue invoice
   - Void invoice
   - Cancel invoice
   - Guard: paid/overpaid/void/cancelled invoice tidak bisa diedit unsafe.
5. **Booking -> Invoice base generation**
   - Booking conversion menyiapkan base invoice `full_payment` dengan status `draft`.
   - Tetap backward-compatible terhadap flow existing.

## Key Decisions
1. **One booking can have multiple invoices**
   - Didukung via index `(booking_id, invoice_type)` dan penghapusan unique lama.
2. **Invoice types in Phase 8A**
   - `down_payment`, `balance_payment`, `full_payment`, `additional_charge`, `cancellation_fee`, `refund`
   - Di fase ini baru lifecycle hardening & data model readiness, bukan full orchestration payment allocation.
3. **Issued/Paid edit safety**
   - Edit unsafe diblokir di controller dengan guard `isEditable()`.

## Files
- `database/migrations/2026_05_20_210000_harden_invoice_lifecycle_fields.php`
- `app/Models/Invoice.php`
- `app/Models/Booking.php`
- `app/Services/InvoiceService.php`
- `app/Http/Controllers/Finance/InvoiceController.php`
- `app/Http/Requests/Finance/UpdateInvoiceRequest.php`
- `app/Http/Requests/Finance/InvoiceLifecycleActionRequest.php`
- `resources/views/modules/invoices/edit.blade.php`
- `resources/views/modules/invoices/show.blade.php`
- `resources/views/modules/invoices/index.blade.php`
- `routes/web.php`

## Test Guide
1. Run migration:
   - `php artisan migrate`
2. Open invoice index/detail:
   - verify no page break.
3. Open invoice edit for draft invoice:
   - update subtotal/discount/tax.
   - verify total & balance recalculated.
4. Issue invoice:
   - status changes to `issued`.
5. Void/cancel invoice (non-paid):
   - status changes accordingly.
6. Try edit paid/overpaid/void/cancelled invoice:
   - must be blocked by guard.
7. Verify route availability:
   - `invoices.edit`, `invoices.update`, `invoices.issue`, `invoices.void`, `invoices.cancel`.

## Deferred to Phase 8B+
1. Payment table/module and payment confirmation flow.
2. Overpayment allocation/deposit logic.
3. Refund allocation mechanics.
4. Multi-invoice generation automation rules per milestone (DP/balance split orchestrator).

