# CODEX MASTER PROMPT — VOYEX CRM

You are working on an existing Laravel project named VOYEX CRM.

Important:
This project already exists and has been developed. Do not rebuild it from scratch.
Your task is to audit, align, fix, improve, and complete the existing system according to the business workflow below.

## Business Workflow

```text
Request Source
→ Customer / Agent
→ Inquiry
→ Itinerary
→ Quotation
→ Quotation Validation
→ Negotiation / Revision
→ Booking
→ Invoice
→ Payment
→ Operation / Service Date
→ Adjustment
→ Settlement
→ Closed
```

## Core Concepts

- Inquiry is the sales entry point.
- Itinerary is the planning engine.
- Quotation is the pricing engine.
- Quotation Validation checks rate, markup, vendor/provider details.
- Booking is the operation engine.
- Invoice is the billing engine.
- Payment is the payment tracking engine.
- Adjustment handles changes after booking.
- Settlement is the final finance and operation check before closing.

## Existing Project Handling Rules

1. Inspect first, then modify.
2. Do not delete existing working features unless clearly wrong.
3. Preserve existing data where possible.
4. Use new migrations for database changes.
5. Avoid editing old migrations unless the project has not been deployed.
6. Avoid hardcoded status strings spread across controllers/views.
7. Centralize statuses in enum/config classes.
8. Use transactions for critical flows.
9. Add activity logs for important actions.
10. Explain every changed file.

## Required Modules

- Customer / Agent Management
- Inquiry Management
- Itinerary Management
- Quotation Management
- Quotation Validation
- Booking Management
- Invoice Management
- Payment Management
- Operation / Service Date
- Adjustment / Amendment
- Settlement / Final Closing
- Vendor Management
- Role and Permission Management
- Activity Log

## Business Rules

- Inquiry must be linked to a customer or agent.
- Inquiry can create itinerary.
- Itinerary can generate quotation.
- Quotation must support versioning.
- Quotation accepted must not be edited directly.
- Booking can only be created from accepted quotation.
- Invoice must be created from booking.
- Payment must be linked to invoice.
- Changes after booking must be handled using adjustment.
- Booking can only be closed after service is completed and finance is settled.
- Closed data must be read-only.

## Priority Order

1. Audit current project structure.
2. Identify implemented, partial, missing, inconsistent modules.
3. Standardize status lifecycle.
4. Fix database relationships and missing fields.
5. Fix Inquiry → Itinerary → Quotation flow.
6. Fix Quotation Validation.
7. Fix Quotation → Booking conversion.
8. Fix Booking → Invoice → Payment flow.
9. Add Operation / Adjustment / Settlement if incomplete.
10. Improve dashboard and reporting.
11. Prepare production readiness.

## Coding Standards

- Use Laravel best practices.
- Use FormRequest for validation.
- Use Service classes for business logic.
- Use Enum/config for statuses.
- Use Policies/Middleware/Permissions for access.
- Use DB transactions for conversion, invoice, payment, adjustment, settlement.
- Use activity logs for important actions.
- Keep controller methods clean.

## Required Response Format After Each Work Session

Please report:

1. Files inspected
2. Files changed
3. Business rule supported
4. What was fixed
5. What still needs work
6. How to test
7. Risk level
