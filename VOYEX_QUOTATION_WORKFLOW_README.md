# VOYEX CRM — Quotation Workflow Optimization Blueprint

## Phase 3 Status Standardization Note

Current final logical quotation statuses:

```text
draft
need_validation
ready_to_send
sent
revision_requested
under_revision
need_revalidation
approved
booking_in_progress
converted_to_booking
rejected
lost
cancelled
```

Backward-compatible legacy mapping:

```text
pending_validation -> need_validation
pending_revalidation -> need_revalidation
customer_approved -> approved
booking_created -> converted_to_booking
```

Production data cleanup must be explicit:

```bash
php artisan quotations:normalize-status --dry-run
php artisan quotations:normalize-status --apply
```

## 1. Context

Project VOYEX CRM is already running, but the current workflow for Inquiry, Itinerary, Quotation, Booking, Invoice, Payment, and Operation is not yet optimal.

The system must be improved so every quotation has a clear workflow status, clear next action, clear PIC/handler, clear validation state, clear revision history, and clear connection to booking, invoice, payment, and actual operation.

This project follows the main travel agent business flow:

```text
Inquiry → Itinerary / Direct Quotation → Quotation → Validation → Send → Revision / Approval / Cancel / Lost → Booking → Invoice → Payment → Operation → Final Invoice → Complete
```

The existing blueprint already defines the core sales process as:

```text
Inquiry Received → Data Entry → Assign to Agent → Follow-up → Create Quotation → Send to Customer → Revision/Approval → Convert to Booking → Confirm Services → Generate Invoice → Payment Processing → Document Preparation → Departure
```

This refactor must improve that flow without breaking existing features.

---

## 2. Main Goal

Optimize quotation workflow so that:

1. Quotation is not just a price document.
2. Quotation becomes a controlled commercial workflow.
3. Every stage is visible in UI/UX.
4. Every status transition is validated by business rules.
5. Every revision has history.
6. Every item validation is tracked.
7. Booking issues can return quotation to revision/revalidation.
8. Invoice and payment status are connected clearly.
9. Operation adjustments can update final invoice.
10. Existing production data must not be broken.

---

## 3. Required Core Workflow

### 3.1 Inquiry Handling

```text
Customer/Agent Request
→ Create Customer/Agent
→ Create Inquiry
→ Assign to Reservation / Auto-take by first Reservation user
→ Create Itinerary or Direct Quotation
```

Rules:

- Inquiry may be created without `assigned_to`.
- If a Reservation user opens/handles an unassigned inquiry, assign automatically to that user.
- Inquiry must track `handled_by`, `assigned_to`, `status`, `source`, `priority`, `deadline`, and `next_action`.
- Inquiry can generate quotation directly.
- Inquiry can be used as reference for itinerary.
- Inquiry should become `quoted` once quotation is created.
- Inquiry should become `cancelled`, `lost`, `pending`, or `completed` based on downstream quotation/booking/invoice result.

---

### 3.2 Itinerary Handling

```text
Inquiry → Itinerary Draft → Generate Quotation
```

Rules:

- Itinerary can be created from inquiry.
- Itinerary can generate quotation.
- Itinerary must remain reusable even if quotation is cancelled/lost.
- Do not cancel itinerary automatically when quotation is cancelled.
- If quotation revision changes itinerary details, create/update itinerary revision carefully.

Recommended itinerary statuses:

```text
draft
linked_to_inquiry
quoted
revised
approved_reference
archived
```

---

### 3.3 Quotation Flow

```text
Draft
→ Pending Validation
→ Validated
→ Ready to Send
→ Sent
→ Under Revision / Approved / Cancelled / Lost / Pending
→ Booking In Progress
→ Invoiced
→ Waiting Payment
→ In Operation
→ Operation Adjustment
→ Finalized
→ Completed
```

Required quotation statuses:

```php
draft
pending_validation
validated
ready_to_send
sent
under_revision
pending_revalidation
approved
booking_in_progress
booking_issue
invoiced
waiting_payment
pending
in_operation
operation_adjustment
finalized
completed
cancelled
lost
```

Required separate status fields:

```text
status
validation_status
send_status
approval_status
booking_status
invoice_status
payment_status
operation_status
current_stage
next_action
handled_by
revision_number
validity_date
last_sent_at
approved_at
cancelled_at
completed_at
```

Important:

- Do not rely only on one `status` field.
- Use separate status dimensions so UI can show accurate workflow condition.
- Existing status field must remain backward compatible if already used in current code.

---

## 4. Quotation Validation

Validation must be a dedicated page, not only an inline update.

### Required validation checks per quotation item

Each quotation item must be validated against:

```text
vendor/provider
service type
contract rate
markup type
markup value
selling price
availability
validity date
cancellation policy
vendor payment terms
contact person
phone
address
validation notes
validated_by
validated_at
```

### Validation statuses

```text
not_required
pending
partial
valid
expired
needs_revalidation
```

Rules:

- Quotation cannot be sent if `validation_status != valid`.
- Manager/Admin may override validation only with required reason.
- If new item is added, quotation becomes `pending_revalidation`.
- If price, vendor, date, service, quantity, markup, or contract rate changes, item becomes `needs_validation`.
- If validity date expires, quotation becomes `pending_revalidation`.
- Every validation action must be logged.

---

## 5. Quotation Revisions

Do not overwrite approved/sent quotation silently.

Use versioning:

```text
QTN-0001 v1
QTN-0001 v2
QTN-0001 v3
```

Required fields:

```text
quotation_number
version
parent_quotation_id
revision_reason
revision_requested_by
revision_requested_at
created_from_revision_id
```

Rules:

- Sent or approved quotation cannot be edited directly.
- Create new revision for customer changes.
- New or changed items must be revalidated.
- Old versions must remain readable.
- Only one active revision should be considered current.
- UI must show revision history.

---

## 6. Booking Integration

Booking is created only from approved quotation.

```text
Quotation Approved
→ Create Booking
→ Create Booking Items from Quotation Items
→ Vendor Confirmation
→ Booking Confirmed / Booking Issue
```

Booking item statuses:

```text
pending_vendor_confirmation
confirmed
unavailable
replaced
cancelled
used
not_used
charged_cancelled
```

Rules:

- If vendor item is unavailable, booking status becomes `booking_issue`.
- Quotation must return to `booking_issue` or `under_revision`.
- Replacement item must go through validation again.
- After customer approves revised quotation, booking can continue.
- Voucher generation belongs in Booking module.

---

## 7. Invoice and Payment

Initial invoice is generated after quotation approval and sufficient booking confirmation.

Invoice statuses:

```text
draft
issued
sent
partially_paid
paid
overpaid
cancelled
revised
disputed
```

Payment statuses:

```text
unpaid
dp_paid
partially_paid
fully_paid
overpaid
deposit_available
overdue
```

Rules:

- If payment due date passes and no DP/payment is made, quotation/inquiry becomes `pending` or `overdue`.
- If validity date expires before payment, quotation must be revalidated before continuing.
- If service date passes without payment/confirmation, inquiry and quotation may become `cancelled` or `lost`.
- Overpayment must be saved as customer deposit.
- Outstanding balance must remain visible until paid.

---

## 8. Operation and Final Invoice

During operation, actual usage may differ from approved quotation.

Possible operation changes:

```text
service item cancelled without charge
service item cancelled with charge
service item added last minute
service item replaced
price changed
quantity changed
actual usage different from quotation
```

Rules:

- Every operation adjustment must be logged.
- Every price/item change must be communicated to customer/agent.
- Final invoice must be based on actual usage.
- Booking and invoice must be finalized only after operation is completed.
- Inquiry, quotation, booking, and invoice become `completed` only after payment is fully settled.

---

## 9. Required New/Improved Tables

Add migrations carefully and safely. Do not drop existing data.

Recommended tables:

```text
quotation_status_logs
quotation_revisions
quotation_validation_logs
quotation_item_validations
quotation_send_logs
quotation_approval_logs
booking_item_logs
operation_adjustments
customer_deposits
payment_allocations
workflow_tasks
```

### 9.1 quotation_status_logs

```text
id
quotation_id
old_status
new_status
action
reason
changed_by
changed_at
metadata JSON
created_at
updated_at
```

### 9.2 quotation_item_validations

```text
id
quotation_id
quotation_item_id
vendor_id
provider_name
contact_person
phone
address
contract_rate
markup_type
markup_value
selling_price
availability_status
validity_date
cancellation_policy
payment_terms
validation_status
notes
validated_by
validated_at
created_at
updated_at
```

### 9.3 quotation_revisions

```text
id
quotation_id
parent_quotation_id
quotation_number
version
revision_reason
revision_requested_by
revision_requested_at
created_by
created_at
updated_at
```

### 9.4 operation_adjustments

```text
id
booking_id
quotation_id
quotation_item_id nullable
booking_item_id nullable
adjustment_type
description
old_quantity
new_quantity
old_price
new_price
charge_type
charge_value
final_amount
customer_notified_at
customer_notification_channel
created_by
created_at
updated_at
```

### 9.5 workflow_tasks

```text
id
module_type
module_id
assigned_to
task_type
title
description
status
priority
due_date
completed_at
created_by
created_at
updated_at
```

---

## 10. Required Laravel Structure

Use clean architecture where possible without over-engineering.

Recommended files:

```text
app/Enums/QuotationStatus.php
app/Enums/QuotationValidationStatus.php
app/Enums/QuotationApprovalStatus.php
app/Enums/BookingStatus.php
app/Enums/InvoiceStatus.php
app/Enums/PaymentStatus.php

app/Services/Quotation/QuotationWorkflowService.php
app/Services/Quotation/QuotationValidationService.php
app/Services/Quotation/QuotationRevisionService.php
app/Services/Booking/BookingFromQuotationService.php
app/Services/Invoice/InvoiceFromBookingService.php
app/Services/Operation/OperationAdjustmentService.php

app/Http/Controllers/QuotationValidationController.php
app/Http/Controllers/QuotationRevisionController.php
app/Http/Controllers/QuotationWorkflowController.php

app/Http/Requests/ValidateQuotationItemRequest.php
app/Http/Requests/CreateQuotationRevisionRequest.php
app/Http/Requests/UpdateQuotationStatusRequest.php

resources/views/quotations/show.blade.php
resources/views/quotations/validation.blade.php
resources/views/quotations/partials/workflow-tracker.blade.php
resources/views/quotations/partials/status-badges.blade.php
resources/views/quotations/partials/action-buttons.blade.php
resources/views/quotations/partials/revision-history.blade.php
resources/views/quotations/partials/item-validation-modal.blade.php
```

---

## 11. UI/UX Requirements

### 11.1 Quotation Detail Page

Must show:

```text
Workflow tracker
Quotation summary
Customer/Agent info
Inquiry reference
Itinerary reference
Current status
Validation status
Approval status
Booking status
Invoice status
Payment status
Current stage
Next action
PIC / handled by
Validity date
Revision number
Timeline/activity log
Action buttons based on current status
```

### 11.2 Workflow Tracker

Display:

```text
Inquiry → Itinerary → Quotation → Validation → Sent → Approval → Booking → Invoice → Payment → Operation → Finalized → Complete
```

Color logic:

```text
gray = not started
blue = current
green = completed
yellow = needs attention
red = issue/cancelled/expired
```

### 11.3 Validation Page

Must show item table with:

```text
service date
service type
description clickable
vendor/provider
contract rate
markup
selling price
availability
validity date
validation status
validation action
notes
```

Clicking description opens modal with:

```text
vendor/provider detail
contact person
phone
address
contract rate
markup type
markup value
selling price
cancellation policy
payment terms
last validation log
```

### 11.4 Action Button Visibility

Show buttons conditionally:

```text
Draft → Submit for Validation
Pending Validation → Validate Items
Validated → Mark Ready to Send
Ready to Send → Send Quotation
Sent → Mark Approved / Request Revision / Mark Lost / Mark Cancelled
Under Revision → Save Revision / Submit for Revalidation
Approved → Create Booking
Booking Issue → Create Revision
Invoiced → Send Invoice / Record Payment
Waiting Payment → Record Payment / Mark Pending
In Operation → Add Operation Adjustment
Finalized → Generate Final Invoice
Completed → View Summary
```

Do not show invalid actions.

---

## 12. Performance Requirements

Optimize without breaking existing behavior.

Required:

```text
Use eager loading for quotation detail
Avoid N+1 queries
Paginate large tables
Index frequently filtered columns
Use database transactions for workflow transitions
Use service classes for business logic
Use policy/permission checks
Cache master data where safe
Do not cache transactional data aggressively
Move heavy PDF/email tasks to queue
Avoid duplicated calculations
Use computed totals carefully
Use activity logs instead of repeated full scans
```

Recommended indexes:

```text
quotations.status
quotations.inquiry_id
quotations.validity_date
quotations.created_by
quotations.handled_by
quotation_items.quotation_id
quotation_items.service_id
quotation_item_validations.quotation_item_id
quotation_item_validations.validation_status
bookings.quotation_id
invoices.booking_id
payments.invoice_id
workflow_tasks.module_type, module_id
workflow_tasks.assigned_to
workflow_tasks.status
workflow_tasks.due_date
```

---

## 13. Error Prevention Rules

Before coding:

1. Inspect current routes, controllers, models, migrations, views, enums, and existing statuses.
2. Do not rename existing columns without migration compatibility.
3. Do not delete old status values without mapping.
4. Create safe migrations.
5. Add nullable columns first if production data exists.
6. Backfill data safely.
7. Add validation after data is compatible.
8. Use transactions for multi-table updates.
9. Add tests for status transitions.
10. Clear Laravel cache after changes.

Commands after implementation:

```bash
php artisan optimize:clear
php artisan migrate
php artisan test
php artisan route:list
php artisan config:cache
php artisan view:cache
```

For local frontend:

```bash
npm install
npm run build
```

---

## 14. Testing Checklist

Test all scenarios:

```text
Create inquiry without assigned_to
Reservation auto-takes inquiry
Create quotation from inquiry
Generate quotation from itinerary
Submit quotation for validation
Validate all quotation items
Block send if validation incomplete
Send quotation
Request revision
Add item during revision
Require revalidation for new item
Send revised quotation
Approve quotation
Create booking from approved quotation
Handle unavailable booking item
Return to revision from booking issue
Generate invoice
Record DP payment
Mark payment overdue
Continue pending quotation before H-2 with revalidation
Cancel quotation after service date if no response
Start operation
Add last minute item
Cancel item with charge
Cancel item without charge
Generate final invoice
Record outstanding payment
Save overpayment as deposit
Complete inquiry/quotation/booking/invoice after fully paid
```

---

## 15. Acceptance Criteria

The refactor is complete only when:

```text
Quotation workflow is visually clear
Status transitions are controlled
Invalid actions are hidden/blocked
Validation page works per item
Revision history is preserved
Booking issue can trigger quotation revision
Invoice reflects payment state
Operation adjustment can affect final invoice
Activity logs exist
No existing quotation data is lost
No major N+1 query appears on detail page
All critical flows are tested
```

---

## 16. Daily Follow-up Rule Update (2026-05-26)

Tujuan:

```text
Follow-up hanya 1 kali per hari per quotation.
```

Aturan implementasi:

```text
1. Form Add Follow-up hanya berisi:
   - Channel
   - Follow-up At
   - Follow-up Note
2. next_follow_up_at diisi otomatis oleh sistem:
   follow_up_at + 1 hari
3. Jika hari ini quotation sudah di-follow-up:
   - Action Add Follow-up tidak ditampilkan
   - Notification follow-up due/overdue tidak ditampilkan
4. Jika ada hari terlewat tanpa follow-up:
   - follow_up_status = follow_up_overdue
   - notification_type = quotation_follow_up_overdue
5. Saat follow-up baru dicatat:
   - unread notification `quotation_follow_up_due` dan `quotation_follow_up_overdue`
     untuk quotation tersebut langsung ditandai read
```

Terminologi:

```text
expired follow-up -> follow_up_overdue
```

---

## 17. Customer Response Form Update (2026-05-26)

Scope:

```text
Menyederhanakan input customer response agar fokus pada keputusan customer/agent.
```

Aturan implementasi:

```text
1. Field form customer response:
   - Response Channel
   - Response Status
   - Response Note
2. Response Status dibatasi:
   - revision_requested
   - approved
   - cancelled
   - rejected
3. response_at tidak diinput manual:
   - diisi otomatis oleh server saat submit
4. Jika response_status = revision_requested:
   - quotation otomatis masuk under_revision
   - next_action = revise_quotation
```

---

## 18. Quotation Revision Action Update (2026-05-26)

Scope:

```text
Memastikan action Revise Quotation membuka halaman revisi yang jelas dan mengembalikan quotation ke alur send/follow-up setelah revisi tersimpan.
```

Aturan implementasi:

```text
1. Action Revise Quotation menggunakan route quotations.revise.
2. Halaman revisi memakai form quotation existing dalam revision mode.
3. Handler quotation dapat:
   - mengubah detail quotation
   - menambah service item
   - mengurangi service item
   - mengganti service item
   - mengubah harga/qty sesuai permission yang ada
4. Saat revisi disimpan:
   - validasi item lama yang masih sama tetap dibawa.
   - item baru atau item pengganti mengikuti aturan validasi item.
   - jika validation complete atau tidak ada item yang perlu validasi, status menjadi ready_to_send.
   - jika masih ada item yang perlu validasi, status menjadi pending_revalidation.
5. Setelah ready_to_send, quotation bisa di-preview/download dan Mark as Sent kembali.
```

---

## 19. Quotation Handler Action Ownership Rule (2026-05-26)

Rule:

```text
Only the quotation handler/PIC may perform quotation actions.
Non-handler users may open quotation detail as read-only.
```

Handler resolution order:

```text
1. quotations.handled_by
2. inquiry.handled_by
3. inquiry.assigned_to
4. quotations.created_by
5. inquiry.created_by
```

Implementation guidance:

```text
1. Do not put handler ownership rules only in Blade.
2. QuotationActionResolver must hide mutation actions for non-handler users.
3. QuotationPolicy must reject update/delete/validation mutations for non-handler users.
4. Controllers must keep authorization guards for direct URL/API attempts.
5. Role permission alone is not enough; the user must also be the resolved handler.
6. Any exception must be documented here and implemented through policy/service logic.
```

Mutation actions covered:

```text
Edit quotation
Revise quotation
Validate quotation
Preview/send workflow action where it changes status
Mark as sent
Add follow-up
Add customer response
Set pending
Mark lost/cancelled
Mark approved
Create booking from quotation
Cancel quotation item
Mark customer response as used for revision
```
