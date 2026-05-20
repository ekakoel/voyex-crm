# CODEX AUDIT PROMPT — DO NOT MODIFY FILES YET

Audit the existing VOYEX CRM Laravel project.

Do not modify files yet.

Please inspect and report:

1. Existing modules
2. Existing database tables
3. Existing migrations related to core flow
4. Existing model relationships
5. Existing routes
6. Existing controllers
7. Existing Blade views
8. Existing statuses used in each module
9. Existing role and permission structure
10. Existing services/classes for business logic
11. Existing commands/schedulers related to inquiry/quotation/booking
12. Existing activity log coverage
13. Existing bugs or inconsistent workflow
14. Missing parts based on required travel agent workflow

## Required Workflow

```text
Customer / Agent
→ Inquiry
→ Itinerary
→ Quotation
→ Quotation Validation
→ Booking
→ Invoice
→ Payment
→ Operation
→ Adjustment
→ Settlement
→ Closed
```

## Important Things to Check

### Status Consistency

Search all usage of:

```text
status
STATUS_OPTIONS
FINAL_STATUS
validated
valid
final
converted
closed
pending
approved
rejected
```

Compare status values in:

- migrations
- model constants
- controller validation
- form request validation
- blade select options
- badge color config
- dashboard filters
- command/scheduler logic

### Database Safety

Check if any status column uses enum. If yes, report risk of SQL Data truncated errors when new status values are used.

### Quotation Flow

Check:

- Can quotation be generated from itinerary?
- Can one itinerary have multiple quotations?
- Is quotation versioning implemented?
- Is accepted quotation locked?
- Is validation required before sent?
- Are contract rate and markup stored as snapshot?

### Booking Flow

Check:

- Can booking be created only from accepted quotation?
- Does booking store quotation_id?
- Does booking store itinerary_id or snapshot?
- Is invoice generated from booking?
- Is operation status separated from payment status?

### Finance Flow

Check:

- Are invoices linked to booking?
- Are payments linked to invoices?
- Is partial payment supported?
- Is overpayment handled?
- Is deposit/credit balance supported?

### Operation / Adjustment / Settlement

Check:

- Is there operation/service date management?
- Is adjustment implemented?
- Is settlement implemented?
- Can booking be closed safely?

## Output Format

Create a structured audit report:

```text
1. Already Implemented
2. Partially Implemented
3. Missing
4. Inconsistent / Risky
5. Recommended Fixes
6. Priority: High / Medium / Low
7. Suggested File Changes
8. Testing Plan
```

Do not code before the audit report is complete.
