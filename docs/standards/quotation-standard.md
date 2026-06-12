# Quotation Module Standard

Last Updated: 2026-06-12

## Cross-Module UI Note
- Itinerary index `Duration` column should show only the main duration summary and must not append `Break Time` text in either desktop or mobile card layouts.
- Itinerary index `Item List` popup should show `Breakfast` / `Lunch` / `Dinner` badges for each F&B row based on stored `meal_type` or fallback `meal_period`, and the badge visual should follow the compact style used by the `Highlighted` label.
- Itinerary index `Item List` popup should render a left-side triangle marker for each service row, preserve per-row item identity in desktop/mobile layouts, and show the `Highlighted` badge only for the exact itinerary item matched to the configured main experience day point.
- Itinerary create/edit wizard `Review` tab should show the `Highlighted` badge on the exact schedule row whose `main experience` checkbox is active for that day, including F&B rows that also show meal-slot badges.
- Itinerary create/edit pages must not show quotation-specific revision context notices, because one itinerary can be linked or reused across multiple quotations and the form should stay quotation-neutral.

## Data Relationship
- One `Inquiry` can have exactly one `Quotation` (`1:1`).
- Every `Quotation` must link to exactly one `Inquiry` (`quotations.inquiry_id` NOT NULL).
- `quotations.inquiry_id` must be unique so a quotation cannot share its inquiry with another quotation.
- Soft-deleted quotation rows still reserve their `inquiry_id` to keep the one-to-one history unambiguous.

## Mandatory Inputs (Create/Edit)
- `inquiry_id` required.
- `customer_id` required.
- `itinerary_id` optional.

## Inquiry Consistency Rule
- `inquiry_id` and `itinerary_id` are independent form selections.
- Selecting or generating from an itinerary must not overwrite selected inquiry.
- Selected inquiry does not filter, clear, or overwrite selected itinerary.
- A quotation may use selected itinerary items while remaining linked to the inquiry explicitly chosen by the user.
- Existing quotation edit/revision must keep the original `inquiry_id`; the Inquiry field is readonly in edit forms and cannot be changed.
- Final/closed inquiry status must not block editing or revising a quotation that is already linked to that inquiry.
- Customer responses can be recorded repeatedly on active pre-approved quotation states so every customer decision/request is auditable.

## Duration Rule
- `duration_days` editable, minimum `1`.
- `duration_nights` readonly, always `max(duration_days - 1, 0)`.
- If quotation is generated from itinerary, duration auto-syncs from itinerary.

## Service Item Rule
- Service items shown only from active master data.
- Service item list filtered by selected destination and sorted A-Z.
- Service item picker uses single input + dropdown reference.
- Add Service controls should render in one row on desktop, with the Add Service button on the far right and the same 42px height as inputs.
- Service items can be added whether quotation is generated from an itinerary or created manually.
- Generated itinerary items and Add Service items share the same item-row contract:
  - description editable,
  - day editable,
  - quantity editable,
  - rate readonly when service master publish rate is available,
  - rate editable only when service master publish rate is missing,
  - unit price calculated automatically from quantity x rate,
  - row can be removed before save.
- Manual rate input for a service item with missing publish rate must update the related master service rate on save:
  - `publish_rate` uses the submitted quotation rate,
  - `markup_type` becomes `percent`,
  - `markup` becomes `10`,
  - `contract_rate` is derived as the base rate before 10% markup,
  - hotel service rate is stored in `hotel_prices` with `start_date` as today and `end_date` as the end of the current year.
- Generating quotation from itinerary must import every scheduled itinerary service item (`transport_day`, `attraction`, `activity`, `transfer`, `fnb`) even when the service has no rate yet, so manually created itinerary items still appear in quotation items and can enter validation/master-data cleanup.
- Self-booked hotel rows are not force-imported when rate is zero because they are not chargeable quotation service lines.
- Generated itinerary items and Add Service items can be dragged between available Day groups in create/edit forms.
- Dropping an item into a Day group must update the row `day_number`; changing `duration_days` must refresh available Day drop zones.
- The final row order from create/edit/generate/drag-drop must be persisted to `quotation_items.sort_order` so quotation detail, subsequent edits, and downstream reviews keep the same service sequence.
- Day is represented by each item group header; item rows must keep `day_number` as a hidden field and must not show a separate Day column/input.
- Quotation item row controls must share a consistent 42px control height and align from the top of the row.
- Drag handle and remove action must be square icon-only controls (`42px x 42px`) so item inputs receive priority width.
- Quotation item headers and rows must use the same desktop column contract: action, description, qty, rate, unit price, action (`1/4/1/2/3/1` on the 12-column grid).
- Item rows must not show Adult/Child Publish Rate badges; pax type metadata stays hidden in `serviceable_meta` when needed.
- Service item description must use `Service Type: Service name`.
- Activity and F&B quotation item descriptions must use `Service Type: Service Name - Vendor Name` when vendor is available.
- Activity and F&B service items with passenger-specific rates must keep `serviceable_meta.pax_type` (`adult` / `child`) across generate, Add Service, edit, detail, and validate quotation flows.
- Activity and F&B Add Service pickers must load adult/child publish rate, contract rate, and markup from the corresponding master-service pax fields; child fallback may use adult master values only when child values are empty.
- Activity and F&B quotation descriptions should include pax label and vendor region when that metadata exists, following `Service Type: Service Name (ADULT|CHILD) - Vendor Name - Region`.
- Hotel service item description must use `Hotel: Hotel Name - Room Name`.
- Quotation detail page must render service items using persisted `sort_order` within each day group; do not rebuild the visual order from `visit_order`, `start_time`, or recreated record IDs when an explicit sort order already exists.
- Item recalculation during typing should be scheduled through `requestAnimationFrame` to avoid repeated total recalculation in the same frame.
- Create/edit quotation form UI labels, button text, placeholders, and JavaScript status messages must use `ui_phrase()`.
- Remove unused UI helper code when its corresponding visible control is removed from the form.
- Regenerating from itinerary may replace existing generated/service item rows and must ask for confirmation first.
- Label format:
  - with vendor: `item - vendor/provider - city`
  - without vendor: `item - city`

## Revision and Validation Carry-over
- The quotation creator can edit or create a revision for their own quotation when they have quotation update permission.
- Draft and pending quotation updates may edit the current quotation directly.
- Sent/ready quotation revisions must use in-place versioning on the same quotation row because `quotations.inquiry_id` is unique for the one-to-one inquiry quotation rule.
- Locked quotation stages must not clone a new quotation row with the same inquiry; users must Start Revision first, then edit while the same quotation is `under_revision`.
- The create/edit form must submit the existing `quotation_items.id` for persisted item rows.
- Existing item rows that were already validated must keep `is_validated`, validator metadata, validation notes, and validated item status when saved again.
- New service rows without an existing `quotation_items.id` must be created as new active items and must go through validation when their service type requires validation.
- Revisions must stay inside the quotation workflow unless the user explicitly starts an itinerary revision.

## Inquiry Filter in Quotation Form
- Show inquiries owned by current user and unhandled inquiries.
- Exclude inquiries that already have any linked quotation.
- Edit quotation may keep its own current inquiry selected, but cannot switch to an inquiry owned by another quotation.
- Show the single quotation availability count for each inquiry option.

## Status Lifecycle
- `draft`
- `need_validation`
- `ready_to_send`
- `sent`
- `revision_requested`
- `under_revision`
- `need_revalidation`
- `approved`
- `booking_in_progress`
- `converted_to_booking`
- `rejected`
- `lost`
- `cancelled`
- Backward-compatible legacy statuses still readable: `pending_validation`, `pending_revalidation`, `customer_approved`, `booking_created`, and historical aliases such as `accepted` / `converted`.

## Recommended Quotation Flow
1. Create quotation manually or generate quotation items from itinerary.
2. Save quotation as `need_validation` when at least one service item needs validation.
3. Validate required service items and revise quotation content while it is still editable.
4. Quotation may become `ready_to_send` only when validation progress is 100%, or when no service item requires validation.
5. `ready_to_send` quotation can be marked as `sent`; marking sent creates the initial `quotation_sent` follow-up record and schedules the next follow-up for H+3.
6. Customer/agent response is recorded after the quotation is sent.
7. Customer/agent approval moves the quotation to `approved`.
8. If customer/agent requests revision, the active sent quotation moves to `revision_requested`.
9. `revision_requested` and `ready_to_send` can use Start Revision while the quotation is not approved or closed; `sent` must wait for a customer response before revision starts.
10. Start Revision increments the quotation `revision_number` in place, marks the same quotation as `under_revision`, writes a revision log when available, and redirects directly to the quotation revision form.
11. Saving revision keeps already validated unchanged items valid and only new/changed validation-required items need validation.
12. If revision adds validation-required items, quotation moves to `need_revalidation`; if no new validation is required, quotation returns to `ready_to_send`.
13. After revalidation reaches 100%, quotation returns to `ready_to_send`, then can be sent again.
14. Each revision can be sent again and receives its own non-duplicate `quotation_sent` follow-up record.
15. Booking creation is available only from logical `approved` quotations with 100% validation (`valid` or `validated`) and at least one item.
16. After booking creation, quotation moves to `converted_to_booking`.

## Quotation PDF
- Clicking `Preview / Download PDF` from quotation action buttons should open the generated PDF in a new browser tab.
- Preview / download quotation PDF must render quotation content only; itinerary schedule/detail sections must not be included even when the quotation is linked to an itinerary.
- Quotation PDF header must include compact metadata for `Customer`, `Inquiry`, current `Version/Revision`, `Itinerary`, `Service Date`, and `Pax (Adult / Child)` so key context is visible without consuming excessive vertical space.
- Quotation PDF compact metadata order must be:
  - first row: `Version`, `Service Date`, `Pax (Adult / Child)`,
  - second row: `Customer`, `Inquiry`, `Itinerary`.
- `Valid Until` should appear once in the compact metadata area under `Version`; it must not be duplicated again under `Service Date`.
- Quotation PDF top header should only show the document title and quotation number; duplicate `Order Number`, `Date`, and `Valid Until` lines must not be repeated in the top-most header block when that context already exists in the compact metadata area below.
- Quotation PDF itinerary metadata should show itinerary name as the primary value and use duration summary instead of raw itinerary id.
- Quotation PDF pax metadata should render in compact inline form: `Adult: N | Child: N`.
- Service item order in quotation PDF must use the same grouped-by-day ordering as quotation detail:
  - day-based items first in ascending day order,
  - `without_day` items under `Additional Services`,
  - within each group prioritize persisted `sort_order`, then itinerary metadata fallback (`visit_order`, `start_time`), then item id.
- Quotation PDF service table must only show customer-safe pricing columns: `Description`, `Unit Price`, `Qty`, `Total`.
- Sensitive internal columns such as `Status`, `Contract Rate`, and `Markup` must not appear in quotation PDF output.

## Validation Status Gate
- `ready_to_send` must never be assigned when validation progress is below 100%.
- `sent` must never be assigned when validation progress is below 100%.
- If a save/revision makes a previously ready quotation incomplete, the quotation must move back to `need_validation` or `need_revalidation`.
- `validated` is retained as a backward-compatible status, but the operational ready-to-send state should use `ready_to_send`.
- Compatibility map:
  - `pending_validation` is treated logically as `need_validation`.
  - `pending_revalidation` is treated logically as `need_revalidation`.
  - `customer_approved` is treated logically as `approved`.
  - `booking_created` is treated logically as `converted_to_booking`.
- `QuotationStatusService` is the central sync point for validation progress, quotation validation status, and validation-gated quotation status.
- Mark as Sent must start from logical `ready_to_send`; controller actions must not silently skip the ready-to-send gate.
- Quotation pages must not silently mutate legacy status data; production data cleanup must use the explicit Artisan command below.

## Normalize Status Command
- Dry run:
  - `php artisan quotations:normalize-status --dry-run`
- Apply:
  - `php artisan quotations:normalize-status --apply`
- The command updates only the approved mapping:
  - `pending_validation -> need_validation`
  - `pending_revalidation -> need_revalidation`
  - `customer_approved -> approved`
  - `booking_created -> converted_to_booking`
- The command checks that target statuses are supported by `QuotationStatus` before applying updates.
- Run dry-run first in production and review the displayed row counts before `--apply`.

## Action Visibility Rule
- `draft`: edit quotation and submit/validate when needed.
- `need_validation`: validate quotation and allow edit.
- `ready_to_send`: preview/download PDF, Mark as Sent, and Start Revision before sending when permitted.
- `sent`: add follow-up, add customer response, preview/download PDF, and set pending. Direct revision/cancel actions are hidden; cancellation or revision request should be recorded through customer response.
- `revision_requested`: Start Revision, add follow-up, and review the customer response trail.
- `under_revision`: edit quotation, validate changed/new items when pending, or Finish Revision when validation is complete.
- `need_revalidation`: validate changed/new items, allow edit, and show Finish Revision when validation is complete.
- `approved`: create booking and preview/download PDF; no edit, revision, or validation without a future official reopen flow.
- `converted_to_booking` / `booking_in_progress`: show linked booking actions.
- Cancelled/lost/completed quotations must not show primary mutation actions.

## Booking Creation From Quotation
- Create Booking is available only for quotation statuses that normalize to logical `approved`, with quotation validation status `valid` or `validated`, and with at least one quotation item.
- Booking creation must only be available to the user who currently handles the linked inquiry/quotation ownership chain.
- Ownership resolution must follow this strict fallback order:
  - `inquiries.handled_by`,
  - if empty, `inquiries.assigned_to`,
  - if still empty, `inquiries.created_by`.
- If `handled_by` is filled with another user, `assigned_to` or `created_by` must not reopen booking access for the current user.
- Booking create form quotation select must show only quotations that:
  - belong to the current resolved handler,
  - are approved,
  - are validation-complete,
  - have at least one item,
  - and are not already linked to an active booking.
- Direct access to `bookings.create?quotation_id=...` or manual POST payloads must be rejected when the quotation is handled by another user or already linked to another active booking.
- When module `bookings` is disabled by Super Admin:
  - all booking routes must stay blocked by module middleware,
  - quotation detail must hide booking-specific action buttons and booking summary fields,
  - quotation workflow UI must not show booking and operation status cards/steps,
  - quotation status labels and notices should avoid exposing downstream booking wording; use generic approved/downstream wording where needed,
  - reservation dashboard must not render booking wording, booking buttons, or booking alerts,
  - invoice and payment screens must hide booking labels/numbers and avoid booking-specific helper copy,
  - shared helper copy in inquiry/quotation context must avoid mentioning booking flow while the module is unavailable.

## Revision Card and History
- Detail quotation must show the current version/revision card with revision label, current status, last revision date, last revised by, total revisions, and validation progress.
- Revision history sidebar should show compact revision cards only; users can click a card to open a detail modal.
- Revision detail modal uses existing data only: in-place `quotation_revisions` logs, quotation status logs, customer responses, and item validation progress.
- History must not invent unavailable data. Safe fallbacks are `Original quotation`, `Not revised yet`, and `No revision history`.
- Customer response `revision_requested` stores requested changes from the response note and is linked to the next started revision through `quotation_revision_id` when the column exists.
- Start Revision must not ask for a revision reason or customer response selection. Users select one or more pending customer revision responses from the revision form sidebar and mark the selected responses as handled for the active revision.
- Any unhandled customer response with revision requested status blocks `ready_to_send` and `sent`; the quotation must remain or return to `revision_requested` until those responses are handled from the revision form sidebar.
- Adding a revision response while quotation is already `under_revision` or `need_revalidation` records the response as additional context and must not reset the quotation back to `revision_requested`.
- Revision History, Follow-up History, and Customer Response History cards should show a compact scrollable list so roughly three records are visible before internal scrolling.
- Follow-up History must render human-readable event labels from `quotation_follow_ups.follow_up_type` and keep both automatic system entries (`quotation_sent`) and manual customer follow-up entries in descending actual event time.
- Manual quotation follow-up records must store `follow_up_type = customer_follow_up`; detail page should not rely on raw `channel` alone to determine the meaning of a follow-up row.
