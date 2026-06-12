# Inquiry Module Standard

Last Updated: 2026-06-08

## Ownership & Assignment
- Primary operational ownership uses `handled_by`.
- `assigned_to` remains user-facing and is synchronized to `handled_by` when claimed/assigned.

## Eligibility for Quotation Flow
- Inquiry must not be final (`converted_to_booking`).
- Inquiry must be either:
  - handled by current user, or
  - unhandled (`handled_by`/`assigned_to` empty).
- Inquiry must not already have a linked quotation.

## Quotation Form Inquiry Dropdown
- Must include owned + unhandled inquiries only.
- Must exclude inquiries that already have a quotation.
- Must display the single quotation availability count in option label for context.

## Validation Rules
- Inquiry selected in quotation flow must pass server-side ownership/eligibility checks.
- Inquiry with any other linked quotation is rejected in create/edit quotation flow.
- Inquiry with any linked quotation is locked for edit.
- Edit inquiry actions must be hidden on index and detail screens when a linked quotation exists.
- Generate Quotation actions must be hidden when a linked quotation exists.
- Direct access to edit/update routes must redirect back to inquiry detail when a linked quotation exists.

## Related Statuses
- Inquiry final state: `converted_to_booking`.
- Inquiry and quotation relationship: exactly one quotation per inquiry, enforced by app validation and database unique index.
- Soft-deleted quotations still count as linked quotations for one-to-one enforcement.

## Deadline Reminder Notification
- Top navigation shows an inquiry deadline reminder icon only for inquiries assigned/handled by the current user.
- Inquiry creator must not receive the reminder unless they are also the assigned/handled user.
- Reminder only applies when inquiry has no linked quotation.
- Reminder windows by priority:
  - `low`: H-1 and H-0.
  - `normal`/`medium`: H-2, H-1, and H-0.
  - `high`: H-7 through H-0.
- Polling endpoint and initial navbar count must use the same reminder query helper.
