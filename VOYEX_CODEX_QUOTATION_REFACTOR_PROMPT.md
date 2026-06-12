# CODEX EXECUTION PROMPT — VOYEX CRM QUOTATION WORKFLOW REFACTOR

You are working on an existing Laravel project named VOYEX CRM.

Repository context:
- Project already runs.
- Do not rebuild from scratch.
- Current flow exists but is not optimal.
- Main goal is to refactor and optimize quotation workflow, statuses, validation, revision, booking connection, invoice connection, and UI/UX visibility.
- Prioritize safe incremental changes.
- Avoid breaking existing data and routes.

Use the file `VOYEX_QUOTATION_WORKFLOW_README.md` as the source of truth.

---

## PRIMARY OBJECTIVE

Refactor the existing quotation process so it becomes a clear workflow engine:

```text
Inquiry → Itinerary / Direct Quotation → Quotation Draft → Validation → Ready to Send → Sent → Revision / Approval / Lost / Cancelled → Booking → Invoice → Payment → Operation → Final Invoice → Completed
```

The UI must clearly show:
- current stage
- quotation status
- validation status
- approval status
- booking status
- invoice status
- payment status
- operation status
- next action
- responsible user/PIC
- revision number
- validity date
- risk/warning indicators

---

## IMPORTANT SAFETY RULES

Do not make destructive changes.

Before coding:
1. Inspect current migrations.
2. Inspect current models.
3. Inspect current controllers.
4. Inspect current quotation routes.
5. Inspect current Blade views.
6. Inspect current quotation status values.
7. Inspect current booking and invoice relationships.
8. Inspect current permission/role logic.

Then create a step-by-step plan before editing files.

Do not:
- drop existing columns
- rename existing columns without backward-compatible migration
- delete existing statuses without mapping
- replace working modules blindly
- create duplicate routes with conflicting names
- break PDF generation
- break quotation calculation
- break existing create/edit/detail pages

---

## PHASE 1 — ANALYZE EXISTING PROJECT

Check these areas:

```text
app/Models/Quotation.php
app/Models/QuotationItem.php
app/Models/Inquiry.php
app/Models/Itinerary.php
app/Models/Booking.php
app/Models/Invoice.php

app/Http/Controllers/*Quotation*
app/Http/Controllers/*Inquiry*
app/Http/Controllers/*Booking*
app/Http/Controllers/*Invoice*

resources/views/**/quotations/**
resources/views/modules/**/quotations/**
routes/web.php
routes/api.php
database/migrations
database/seeders
```

Create a short internal implementation note:
- current status fields found
- current routes found
- current quotation item structure
- current relation structure
- gaps against required workflow
- safest implementation path

---

## PHASE 2 — ADD ENUMS SAFELY

Create enums if they do not exist:

```text
app/Enums/QuotationStatus.php
app/Enums/QuotationValidationStatus.php
app/Enums/QuotationApprovalStatus.php
app/Enums/BookingStatus.php
app/Enums/InvoiceStatus.php
app/Enums/PaymentStatus.php
```

QuotationStatus must include:

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

Important:
- If existing code uses different status values, create mapping helpers instead of breaking existing flow.
- Update model casts only if database values are compatible.
- Avoid enum cast if legacy values may cause runtime error. Add normalization first.

---

## PHASE 3 — ADD SAFE MIGRATIONS

Add missing fields to `quotations` only if they do not exist:

```text
validation_status nullable
send_status nullable
approval_status nullable
booking_status nullable
invoice_status nullable
payment_status nullable
operation_status nullable
current_stage nullable
next_action nullable
handled_by nullable foreignId users
revision_number default 1
last_sent_at nullable timestamp
approved_at nullable timestamp
cancelled_at nullable timestamp
completed_at nullable timestamp
```

Create tables if missing:

```text
quotation_status_logs
quotation_revisions
quotation_validation_logs
quotation_item_validations
quotation_send_logs
quotation_approval_logs
workflow_tasks
```

Optional if operation module already exists:

```text
operation_adjustments
customer_deposits
payment_allocations
booking_item_logs
```

Migration rules:
- Use `Schema::hasColumn`.
- Use nullable fields for production safety.
- Add indexes.
- Do not modify existing columns unless necessary.
- If status column length is too small, safely expand it to string length 80.

---

## PHASE 4 — CREATE SERVICES

Create service classes:

```text
app/Services/Quotation/QuotationWorkflowService.php
app/Services/Quotation/QuotationValidationService.php
app/Services/Quotation/QuotationRevisionService.php
app/Services/Booking/BookingFromQuotationService.php
```

### QuotationWorkflowService responsibilities

```text
transition status safely
validate allowed status transition
update next_action
update current_stage
write quotation_status_logs
create workflow task if needed
wrap multi-table updates in DB transaction
```

### QuotationValidationService responsibilities

```text
load quotation items
validate item contract rate/markup/vendor
mark item validation status
calculate validation summary
set quotation validation_status
block send if invalid
set pending_revalidation when item changes
```

### QuotationRevisionService responsibilities

```text
create new revision/version
preserve old quotation
copy quotation items carefully
link parent quotation
mark changed/new items as needs_validation
write revision log
```

### BookingFromQuotationService responsibilities

```text
create booking from approved quotation
copy quotation items into booking items
set booking item vendor confirmation status
update quotation booking_status
handle booking issue
```

---

## PHASE 5 — CONTROLLERS AND ROUTES

Add or update controllers safely:

```text
QuotationWorkflowController
QuotationValidationController
QuotationRevisionController
```

Routes should include:

```php
GET quotations/{quotation}/validation
POST quotations/{quotation}/submit-validation
POST quotations/{quotation}/validate-items
POST quotations/{quotation}/mark-ready-to-send
POST quotations/{quotation}/send
POST quotations/{quotation}/request-revision
POST quotations/{quotation}/approve
POST quotations/{quotation}/cancel
POST quotations/{quotation}/mark-lost
POST quotations/{quotation}/create-booking
```

Route naming example:

```php
quotations.validation.show
quotations.validation.submit
quotations.workflow.ready-to-send
quotations.workflow.send
quotations.workflow.approve
quotations.workflow.cancel
quotations.workflow.lost
quotations.revisions.create
```

Important:
- Reuse existing controller if project structure already has module-specific controllers.
- Do not create route conflicts.
- Use middleware/auth/permission based on existing project pattern.
- Add policies where needed.

---

## PHASE 6 — UI/UX REFACTOR

Refactor quotation detail page.

Add partials:

```text
resources/views/quotations/partials/workflow-tracker.blade.php
resources/views/quotations/partials/status-badges.blade.php
resources/views/quotations/partials/action-buttons.blade.php
resources/views/quotations/partials/revision-history.blade.php
resources/views/quotations/partials/item-validation-modal.blade.php
```

If project uses a different path, follow existing path.

### Quotation Detail Must Show

```text
workflow tracker
status badges
current stage
next action
PIC/handler
validity date
revision number
customer/agent
inquiry reference
itinerary reference
quotation items
validation summary
revision history
timeline/activity log
context-aware buttons
```

### Workflow tracker steps

```text
Inquiry
Itinerary
Quotation
Validation
Sent
Approval
Booking
Invoice
Payment
Operation
Finalized
Complete
```

### Button visibility rules

```text
draft → Submit for Validation
pending_validation → Validate Items
validated → Mark Ready to Send
ready_to_send → Send Quotation
sent → Mark Approved / Request Revision / Mark Lost / Mark Cancelled
under_revision → Submit for Revalidation
pending_revalidation → Validate Items
approved → Create Booking
booking_issue → Create Revision
invoiced/waiting_payment → Record/View Payment
in_operation → Add Operation Adjustment
finalized → Generate/View Final Invoice
completed → View Summary
```

Do not show buttons that are not valid for current status.

---

## PHASE 7 — VALIDATION PAGE UI

Create a dedicated validation page.

Table columns:

```text
service date
service type
description
vendor/provider
contract rate
markup type
markup value
selling price
availability
validity date
validation status
notes
action
```

Feature:
- Description is clickable.
- Clicking opens modal with vendor/provider/contact/rate details.
- User can validate/update each item.
- Bulk mark valid only if all required data is present.
- Show warning for expired validity date.
- Show progress: `x/y items valid`.

---

## PHASE 8 — PERFORMANCE OPTIMIZATION

Apply these optimizations:

```text
Use eager loading on quotation detail:
quotation.items
quotation.inquiry.customer
quotation.itinerary
quotation.statusLogs.user
quotation.validationLogs
quotation.revisions
quotation.booking
quotation.invoice
```

Avoid:
- N+1 queries in item rows.
- repeated price calculations in Blade.
- heavy logic inside Blade.
- loading all quotations without pagination.

Add indexes:
```text
quotations.status
quotations.validation_status
quotations.approval_status
quotations.booking_status
quotations.payment_status
quotations.validity_date
quotations.handled_by
quotation_items.quotation_id
quotation_item_validations.quotation_item_id
quotation_status_logs.quotation_id
workflow_tasks.assigned_to
workflow_tasks.status
workflow_tasks.due_date
```

Move heavy tasks to queue:
```text
PDF generation
email sending
notification sending
bulk export
```

---

## PHASE 9 — TESTING

Add tests for:

```text
unassigned inquiry auto-take
create quotation from inquiry
generate quotation from itinerary
submit quotation validation
block send when not valid
send quotation when valid
request revision after sent
new item requires revalidation
approve quotation
create booking from approved quotation
booking issue returns to revision
invoice generation after booking
payment overdue state
operation adjustment affects final invoice
complete only after full payment
```

Run:

```bash
php artisan optimize:clear
php artisan migrate
php artisan test
php artisan route:list
npm run build
```

Fix all errors before finalizing.

---

## PHASE 10 — FINAL REVIEW

Before completing the task, verify:

```text
No broken route
No broken Blade include
No enum mismatch error
No SQL truncated status error
No N+1 query on quotation detail
No invalid action button visible
Existing quotations still open
Existing quotation edit still works
PDF/export still works
User permissions still respected
```

Final output must include:

1. Summary of files changed.
2. Database migrations added.
3. New statuses added.
4. UI pages/partials added.
5. Testing results.
6. Known limitations.
7. Next recommended phase.

---

## CODING STYLE

Follow:
- Laravel best practices.
- PSR-12.
- Thin controller, service-based business logic.
- Database transactions for workflow changes.
- Clear validation requests.
- Authorization checks.
- Clean Blade components/partials.
- No duplicated status logic in Blade.
- No hardcoded role names unless existing system already does that.

Start by inspecting the current codebase, then implement incrementally.