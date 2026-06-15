# VOYEX UI Refactor Changelog

## 2026-06-15 (Inquiry Form Refactor + Safe Itinerary Reference Sync)
- Scope: stabilize inquiry create/edit flow, reduce duplicated controller logic, and stop quotation save failures when an itinerary is reused by another inquiry.
- Updated files:
  - app/Http/Controllers/Sales/InquiryController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/inquiries/_form.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - centralized inquiry form payload validation and assignment normalization so create/update no longer duplicate the same rules and `handled_by` sync logic.
  - reduced inquiry create/edit selector payload size by loading only required customer columns (`id`, `name`, `code`) and handler columns (`id`, `name`).
  - removed dead fallback code for `customer_label` in the inquiry form.
  - changed quotation-to-itinerary inquiry sync so `inquiry_itinerary_references` is treated as a primary itinerary reference only.
  - quotation save now refreshes the reference only when the itinerary already points to the same inquiry, inserts when missing, and silently preserves an existing different itinerary reference.
  - quotation PDF now renders F&B `menu_highlights` as a compact `Menu:` line under the item description and cleans the row typography so the extra detail stays readable.
  - vendor index now precomputes row presentation data once for both desktop and mobile layouts, removing duplicated nested Blade loop logic that made the template fragile.
- Outcome:
  - create/edit inquiry is lighter and easier to maintain.
  - creating quotation no longer fails with duplicate `inq_itinerary_ref_itinerary_unique` when reusing an itinerary across multiple quotations/inquiries.

## 2026-06-12 (Index Activate/Deactivate Restricted To Super Admin And Administrator)
- Scope: hide module index activation toggles from non-admin roles and harden backend toggle endpoints.
- Updated files:
  - app/Models/User.php
  - app/Http/Controllers/Admin/AirportController.php
  - app/Http/Controllers/Admin/ActivityController.php
  - app/Http/Controllers/Admin/DestinationController.php
  - app/Http/Controllers/Admin/FoodBeverageController.php
  - app/Http/Controllers/Admin/HotelController.php
  - app/Http/Controllers/Admin/IslandTransferController.php
  - app/Http/Controllers/Admin/ItineraryController.php
  - app/Http/Controllers/Admin/TouristAttractionController.php
  - app/Http/Controllers/Admin/TransportController.php
  - app/Http/Controllers/Admin/VendorController.php
  - app/Http/Controllers/Sales/CustomerController.php
  - app/Http/Controllers/Sales/InquiryController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/activities/partials/_index-results.blade.php
  - resources/views/modules/airports/index.blade.php
  - resources/views/modules/customers/index.blade.php
  - resources/views/modules/destinations/index.blade.php
  - resources/views/modules/food-beverages/index.blade.php
  - resources/views/modules/hotels/partials/_index-results.blade.php
  - resources/views/modules/island-transfers/index.blade.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/tourist-attractions/partials/_index-results.blade.php
  - resources/views/modules/transports/index.blade.php
  - resources/views/modules/vendors/index.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - added a shared `User::canManageActivationActions()` helper for `Super Admin` and `Administrator`.
  - module index views now hide `Activate` / `Deactivate` actions unless the current user matches that helper.
  - `toggleStatus()` handlers now enforce the same role rule server-side and return `403` for other roles.
  - quotation list soft-delete toggle now follows the same visibility rule because it uses the same toggle endpoint pattern.
- Mandatory audit result:
  - role consistency: pass, UI and backend now use the same role check.
  - security: pass, direct endpoint access is blocked for non-admin roles.
  - data safety: pass, authorization and presentation update only.

## 2026-06-12 (Itinerary Day Planner Inter-Island Transfer Region Filter)
- Scope: make inter-island transfer selection respect row region filtering while keeping all active transfers available for `All Regions`.
- Updated files:
  - resources/views/modules/itineraries/_form.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - island-transfer options now expose complete region metadata (`city`, `province`, `location`) for reliable row-level filtering.
  - transfer option ordering and region option generation now use region fallbacks instead of relying on city only.
  - `Inter Island Transfer` select now ignores the global destination keyword filter so `All Regions` truly shows every active transfer item.
  - selected-row region auto-sync now falls back from city to province/location when master data is incomplete.
- Mandatory audit result:
  - multi-language: pass, existing UI labels reused.
  - performance: pass, filtering remains client-side on preloaded active options with no extra request cycle.
  - data safety: pass, presentation/filtering refactor only.

## 2026-06-12 (Itinerary Edit Form Removes Quotation Context Notice)
- Scope: keep itinerary edit UI neutral even when the itinerary participates in quotation revision flows.
- Updated files:
  - resources/views/modules/itineraries/edit.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - removed the visible `Quotation Revision Context` notice block from itinerary edit.
  - itinerary edit form no longer surfaces copy that implies the itinerary belongs to only one quotation.
  - hidden revision-routing inputs remain intact so compatible backend flows can still complete without showing quotation notice UI.
- Mandatory audit result:
  - multi-language: pass, removed existing UI text only.
  - workflow safety: pass, backend revision parameters remain available.
  - data safety: pass, presentation-only cleanup.

## 2026-06-12 (Itinerary Review Tab Highlighted Badge)
- Scope: mirror main-experience highlight visibility inside the itinerary create/edit review tab.
- Updated files:
  - resources/views/modules/itineraries/_form.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - review-tab row rendering now exposes a dedicated `Highlighted` badge for the schedule row whose main-experience checkbox is active.
  - highlighted review badge uses an amber compact style aligned with other itinerary highlight cues.
  - F&B review rows now support showing meal-slot badge and highlighted badge together without conflicting layout.
- Mandatory audit result:
  - multi-language: pass, visible label uses `ui_phrase()`.
  - layout consistency: pass, review tab now matches itinerary index highlight behavior.
  - data safety: pass, client-side presentation update only.

## 2026-06-12 (Itinerary Item List Highlight Accuracy And Row Markers)
- Scope: improve itinerary index item-list popup clarity and prevent incorrect highlighted badges.
- Updated files:
  - resources/views/modules/itineraries/index.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - popup item rows now use a stable per-row key so highlight resolution no longer depends on duplicated label text.
  - `Highlighted` badge now renders only when a real itinerary main-experience row is matched; no fallback badge is shown on unrelated items.
  - service rows in desktop and mobile popups now use a triangle-right icon on the left to visually separate each item without relying on list bullets.
  - shared row normalization also removes an unsafe fallback path that could cast popup row arrays as strings.
- Mandatory audit result:
  - multi-language: pass, all visible labels still use `ui_phrase()`.
  - layout consistency: pass, desktop and mobile popup item lists now share the same marker and badge rules.
  - data safety: pass, presentation/refactor only with no schema change.

## 2026-06-12 (Itinerary Item List F&B Meal Badges)
- Scope: show meal-slot badges for F&B rows inside the itinerary index item-list popup.
- Updated files:
  - app/Http/Controllers/Admin/ItineraryController.php
  - resources/views/modules/itineraries/index.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - itinerary index eager load now includes F&B `meal_type` plus master `meal_period` so popup labels have reliable source data.
  - item-list popup now keeps per-item metadata instead of reducing rows to plain strings too early.
  - F&B rows now render compact `Breakfast` / `Lunch` / `Dinner` badges in both desktop and mobile popups.
  - duplicate item cleanup now preserves different meal-slot variants of the same F&B name.
- Mandatory audit result:
  - multi-language: pass, visible labels still use `ui_phrase()`.
  - layout consistency: pass, badge shape follows the compact highlighted-label pattern.
  - data safety: pass, presentation and eager-load adjustment only.

## 2026-06-12 (Itinerary Index Duration Hides Break Time)
- Scope: simplify the itinerary index `Duration` column by removing break-time summary noise.
- Updated files:
  - resources/views/modules/itineraries/index.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - removed `Break Time` summary from the itinerary index duration cell in desktop table rows.
  - removed the same `Break Time` summary from the mobile itinerary cards so both layouts stay aligned.
- Mandatory audit result:
  - multi-language: pass, removed existing UI text only.
  - layout consistency: pass, desktop and mobile duration blocks now match.
  - data safety: pass, presentation-only cleanup.

## 2026-06-12 (Booking Module Disable Hides Booking Surfaces)
- Scope: keep booking features invisible and inaccessible across quotation/reservation UI when Super Admin disables the Booking module.
- Updated files:
  - app/Support/QuotationActionResolver.php
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/quotations/show.blade.php
  - resources/views/reservation/dashboard.blade.php
  - tests/Unit/Support/QuotationActionResolverTest.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - quotation action resolver now suppresses `Create Booking`, `View Booking`, vendor confirmation, and booking-operation actions when module `bookings` is disabled.
  - quotation detail workflow summary now removes booking-specific cards, steps, and booking metadata when the booking module is off.
  - quotation status and notice copy now fall back to generic approved/downstream wording so hidden booking states do not leak through badges or alerts.
  - reservation dashboard subtitle and attention alert now avoid booking wording when booking access is unavailable because the module is disabled.
  - invoice, payment, and inquiry helper views now hide booking labels/references and use neutral helper copy when booking is unavailable.
  - unit test coverage now asserts booking actions disappear and booking-issue revision copy becomes generic when the `bookings` module toggle is off.
- Mandatory audit result:
  - module toggle consistency: pass, route middleware stays as backend guard and UI now matches it.
  - multi-language: pass, new visible copy uses `ui_phrase()`.
  - data safety: pass, no schema change required.

## 2026-06-12 (Booking Creation Guarded By Inquiry Handler)
- Scope: make booking creation from approved quotation work only for the responsible handler and only for eligible quotations.
- Updated files:
  - app/Support/Concerns/ResolvesInquiryHandler.php
  - app/Http/Controllers/BookingController.php
  - app/Http/Requests/StoreBookingRequest.php
  - app/Http/Requests/UpdateBookingRequest.php
  - app/Support/QuotationActionResolver.php
  - app/Http/Controllers/Reservation/DashboardController.php
  - tests/Unit/Support/ResolvesInquiryHandlerTest.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - centralized inquiry-handler resolution with strict fallback order `handled_by -> assigned_to -> created_by`.
  - booking create quotation list now shows only approved, validation-complete, unbooked quotations owned by the current resolved handler.
  - direct create-booking access and booking store/update requests now reject quotations handled by another user.
  - quotation detail `Create Booking` action and reservation dashboard ready-to-book source now follow the same ownership rule.
- Mandatory audit result:
  - multi-language: pass, reused existing labels and error copy with `ui_phrase()`.
  - workflow safety: pass, UI filter and backend validation now share the same ownership rule.
  - data safety: pass, no schema change required.

## 2026-06-12 (Quotation PDF Validity De-duplication)
- Scope: remove repeated validity information from the compact service-date metadata cell.
- Updated files:
  - resources/views/pdf/quotation.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - removed `Valid Until` duplication from the `Service Date` metadata block because validity is already shown under `Version`.
- Mandatory audit result:
  - multi-language: pass, no new visible labels.
  - PDF layout: pass, metadata becomes cleaner without increasing height.
  - data safety: pass, presentation-only cleanup.

## 2026-06-12 (Quotation PDF Metadata Row Reordering)
- Scope: reorder compact quotation PDF metadata rows to prioritize operational summary first.
- Updated files:
  - resources/views/pdf/quotation.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - first metadata row now shows `Version`, `Service Date`, and `Pax (Adult / Child)`.
  - second metadata row now shows `Customer`, `Inquiry`, and `Itinerary`.
- Mandatory audit result:
  - multi-language: pass, visible labels use `ui_phrase()`.
  - PDF layout: pass, summary-first ordering improves scan speed without increasing header height.
  - data safety: pass, presentation-only reorder.

## 2026-06-12 (Quotation PDF Header De-duplication And Compact Itinerary/Pax)
- Scope: remove duplicate top-header details and further simplify itinerary plus pax display in quotation PDF.
- Updated files:
  - resources/views/pdf/quotation.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - removed duplicated `Order Number`, `Date`, and top-header `Valid Until` lines so the top-most header only keeps title and quotation number.
  - itinerary metadata now shows itinerary name as the main value and replaces raw itinerary id with duration summary.
  - pax metadata now renders as a single compact inline value: `Adult: N | Child: N`.
- Mandatory audit result:
  - multi-language: pass, visible labels use `ui_phrase()`.
  - PDF layout: pass, header becomes shorter and less repetitive.
  - data safety: pass, reused existing quotation and itinerary fields only.

## 2026-06-12 (Quotation PDF Compact Context Expansion)
- Scope: extend the compact PDF metadata strip with itinerary, service date, and pax context while keeping the header height efficient.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/pdf/quotation.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - PDF controller now eagerly loads itinerary relation for quotation preview/download.
  - compact metadata area now includes `Itinerary` number/name, `Service Date`, and `Pax (Adult / Child)` in an additional condensed row.
  - itinerary number uses the existing itinerary record id (`#id`) because the current data model has no dedicated `itinerary_number` field.
- Mandatory audit result:
  - multi-language: pass, visible labels use `ui_phrase()`.
  - PDF layout: pass, expanded context stays inside a compact two-row metadata table.
  - data safety: pass, reused existing quotation and itinerary data without schema change.

## 2026-06-12 (Quotation PDF Compact Header Metadata)
- Scope: show customer, inquiry, and quotation version context in PDF without expanding the page header too much.
- Updated files:
  - resources/views/pdf/quotation.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - added a compact three-column metadata strip below the PDF title for `Customer`, `Inquiry`, and `Version`.
  - customer block now shows the resolved customer/agent name plus compact contact context when available.
  - inquiry block now shows inquiry number with compact source/deadline context, and version block shows revision label plus service/validity context.
- Mandatory audit result:
  - multi-language: pass, visible labels use `ui_phrase()`.
  - PDF layout: pass, metadata stays condensed into one short row to preserve vertical space for service items.
  - data safety: pass, reused existing loaded quotation/inquiry/customer relations only.

## 2026-06-12 (Quotation PDF Opens In New Tab)
- Scope: keep quotation detail action flow intact while opening PDF preview/download in a separate browser tab.
- Updated files:
  - resources/views/modules/quotations/partials/action-buttons.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - `Preview / Download PDF` action button now renders with `target="_blank"` and `rel="noopener"`.
  - the same new-tab behavior is applied inside the quotation action dropdown path as well.
- Mandatory audit result:
  - multi-language: pass, no new visible copy.
  - UX consistency: pass, existing PDF links on quotation index already used new-tab behavior and detail actions now match.

## 2026-06-12 (Quotation PDF Aligned With Detail Services)
- Scope: remove itinerary section from quotation PDF and make service ordering/layout follow quotation detail.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/quotations/show.blade.php
  - resources/views/pdf/quotation.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - quotation PDF now always uses the quotation-only template, even when the quotation is linked to an itinerary.
  - removed itinerary block from quotation PDF output so preview/download focuses on quotation content only.
  - centralized quotation service grouping/sorting in controller helper and reused it for both detail page and PDF.
  - PDF service table now mirrors detail-page grouping with `Day N` / `Additional Services` sections.
  - hidden internal-only columns from PDF output: `Status`, `Contract Rate`, and `Markup`.
- Mandatory audit result:
  - multi-language: pass, user-facing labels use `ui_phrase()` or existing wording.
  - PDF layout: pass, simplified to one consistent quotation document.
  - data safety: pass, no schema change required.

## 2026-06-12 (Quotation Follow-up History Accuracy)
- Scope: make quotation detail `Follow-up History` reflect real follow-up events with correct labels and ordering.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Services/Quotation/QuotationFollowUpService.php
  - resources/views/modules/quotations/show.blade.php
  - tests/Unit/Workflow/QuotationFollowUpWorkflowTest.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - manual follow-up records now persist explicit `follow_up_type = customer_follow_up`.
  - quotation detail now normalizes follow-up rows into human-readable history entries instead of showing raw `channel` values only.
  - automatic `quotation_sent` entries remain visible as system history items, while manual follow-ups show their actual communication channel.
  - detail card now sorts by effective follow-up event time and shows both creator plus handled-by context when relevant.
- Mandatory audit result:
  - multi-language: pass, all new visible labels use `ui_phrase()` or existing UI helpers.
  - dark/light mode: pass, reused existing status card patterns only.
  - data safety: pass, no destructive migration required.

## 2026-06-12 (Activity Adult/Child Rate Alignment in Quotation)
- Scope: make Activity quotation pricing and labeling behave like F&B across create, generate, edit, detail, and validate flows.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Services/ItineraryQuotationService.php
  - resources/views/modules/quotations/_form.blade.php
  - resources/views/modules/quotations/validate.blade.php
  - tests/Unit/Services/ItineraryQuotationServiceTest.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - Activity Add Service picker now supports pax type selection like F&B.
  - Activity service catalog now exposes adult/child publish rate, contract rate, markup type, and markup so child selections no longer reuse adult values incorrectly.
  - Activity quotation descriptions are now normalized in the same pax-aware format as F&B, including pax label and vendor region when available.
  - Itinerary-generated Activity items now carry vendor region metadata so downstream detail/validation rendering stays consistent without losing pax-aware context.
  - Validation table/card labels now format Activity descriptions with the same pax-aware cleanup used for F&B.
- Mandatory audit result:
  - multi-language: pass, reused existing translated labels and prefixes.
  - multi-currency: pass, no currency conversion logic changed.
  - dark/light mode: pass, no theme class changes.

## 2026-06-12 (Quotation Detail Service Order Persistence)
- Scope: keep quotation detail service ordering consistent with the sequence arranged during create/edit/generate/drag-drop.
- Updated files:
  - database/migrations/2026_06_12_120000_add_sort_order_to_quotation_items_table.php
  - app/Models/QuotationItem.php
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/quotations/_form.blade.php
  - resources/views/modules/quotations/show.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - added persistent `sort_order` on `quotation_items`.
  - backfilled existing quotation item order using the previous detail-page fallback ordering so legacy quotations stay visually stable after migration.
  - create/edit quotation form now submits `items[*][sort_order]` from the current DOM sequence after generate, drag-drop, and reindex.
  - quotation save/update flow now persists `sort_order` together with each service item payload.
  - quotation detail page now prioritizes persisted `sort_order` inside each day group instead of rebuilding the visual sequence from transient fallbacks.
- Mandatory audit result:
  - multi-language: pass, no new user-facing copy beyond documentation.
  - multi-currency: pass, no money formatter behavior changed.
  - dark/light mode: pass, no theme class changes.

## 2026-06-09 (Quotation Repeated Revision Flow)
- Scope: make quotation revision/revalidation repeatable until customer approval while keeping Phase 1-3 status normalization intact.
- Updated files:
  - app/Support/QuotationActionResolver.php
  - app/Services/Quotation/QuotationWorkflowService.php
  - app/Services/Quotation/QuotationCustomerResponseService.php
  - app/Services/Quotation/QuotationFollowUpAutomationService.php
  - app/Services/QuotationRevisionService.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Http/Controllers/Sales/QuotationValidationController.php
  - resources/views/modules/quotations/show.blade.php
  - tests/Unit/Support/QuotationActionResolverTest.php
  - tests/Unit/Workflow/QuotationFollowUpWorkflowTest.php
  - tests/Unit/Workflow/QuotationRevisionValidationCarryOverTest.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - customer response `revision_requested` now stops at logical `revision_requested` instead of silently entering edit mode.
  - Start Revision creates the next quotation revision record, marks it `under_revision`, and redirects to the revision form.
  - action resolver now shows Start Revision, Validate Changed/New Items, Finish Revision, Mark as Sent, and Create Booking according to logical status plus validation state.
  - approved and converted quotation statuses can no longer transition back to validation/revision without a future official reopen flow.
  - validation controller blocks approved/downstream/closed statuses while keeping `under_revision` and `need_revalidation` validatable.
  - revision card now shows current version, status, last revision date/by, total revisions, and validation progress.
  - revision history sidebar now reads the revision chain, status logs, linked customer responses, and item validation progress with safe fallbacks.
  - automatic sent follow-up notes include revision labels and remain non-duplicate per quotation revision.
- Mandatory audit result:
  - multi-language: pass, visible Blade labels use `ui_phrase()`.
  - multi-display: pass, revision card/sidebar use existing responsive card/grid conventions.
  - data safety: pass, no new table and no destructive migration.

### Follow-up Fix
- Start Revision now uses in-place quotation versioning instead of cloning a new quotation row.
- Reason: `quotations.inquiry_id` is intentionally unique for the one-to-one inquiry quotation rule, so cloning a revision with the same inquiry caused `quotations_inquiry_id_unique` violations.
- The legacy clone revision service is no longer injected into `QuotationController` and is marked deprecated for explicit historical snapshot experiments only.

### Revision History UI
- Revision History sidebar now shows compact clickable cards with revision label, trigger, date, status, and validation progress.
- Full revision details moved into per-revision modals to keep the sidebar readable while preserving detailed audit context.

### Sent Action Guard
- Sent quotation no longer shows direct Start Revision, Mark Lost, or Mark Cancelled actions.
- Cancellation and revision requests from sent quotations should be recorded through Customer Response so the decision trail remains auditable.

### Customer Response Selection for Revision
- Pending customer revision responses are selected from the quotation revision sidebar instead of the Start Revision action.
- Only responses selected in the revision sidebar are marked handled; other pending revision responses remain open for later revision cycles.
- Add Customer Response remains available on active pre-approved revision states so repeated customer requests can be recorded without losing the workflow trail.
- Revision History, Follow-up History, and Customer Response History now use compact internal scrolling so long histories do not stretch the detail page vertically.
- Customer Response History rows now follow the compact Revision History pattern with channel, status badge, date, trimmed note, handled state, and creator metadata.
- Customer response recording is now audit-first: every valid submitted response is stored even when the requested quotation status transition is not available for the current quotation state.
- Start Revision now starts the revision immediately and redirects to the quotation revision form; pending customer revision responses are selected and marked handled from the revision sidebar.
- The Start Revision modal and manual Revision Reason input were removed from the active quotation revision flow.
- Quotation Status Summary is now a compact flow card with current status, condensed quotation stages, validation percentage, pending revision response count, and next action.
- Quotation workflow notices now use context-aware messages for sent, revision pending, approved, booking, operation, completed, and closed states instead of generic locked-stage text.
- Quotation item Rate inputs are readonly when service master publish rate exists and editable only when the selected service has no publish rate.
- Manual rate input for missing service publish rates now backfills the related master rate with publish rate, percent markup 10%, and derived contract rate; hotel rows create/update `hotel_prices` for today through year end.
- Itinerary-generated quotation items now include scheduled service items even when their rate is still zero, so manually created itinerary services are carried into quotation validation/master-data cleanup.
- Activity and F&B quotation item descriptions now include vendor names as `Service Type: Service Name - Vendor Name` across generate, Add Service, edit, and detail display when vendor data is available.

## 2026-06-09 (Quotation Status Flow Phase 3)
- Scope: final cleanup for quotation status standardization, status normalization command, and duplicate service clarification.
- Updated files:
  - app/Console/Commands/NormalizeQuotationStatus.php
  - app/Enums/QuotationStatus.php
  - app/Models/Quotation.php
  - app/Support/Workflow/QuotationStatusNormalizer.php
  - app/Support/Workflow/QuotationWorkflow.php
  - app/Services/Quotation/QuotationStatusService.php
  - app/Services/Quotation/QuotationWorkflowService.php
  - app/Services/Quotation/QuotationCustomerResponseService.php
  - app/Services/Quotation/QuotationFollowUpAutomationService.php
  - app/Services/Quotation/QuotationFollowUpService.php
  - app/Services/Quotation/QuotationValidationService.php
  - app/Services/Quotation/QuotationRevisionService.php
  - app/Services/QuotationRevisionService.php
  - app/Services/QuotationItinerarySyncService.php
  - app/Services/Booking/BookingFromQuotationService.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Http/Controllers/BookingController.php
  - app/Http/Controllers/Reservation/DashboardController.php
  - app/Http/Controllers/Manager/DashboardController.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/quotations/show.blade.php
  - tests/Feature/Console/NormalizeQuotationStatusCommandTest.php
  - tests/Unit/Workflow/QuotationStatusServiceTest.php
  - tests/Unit/Workflow/QuotationWorkflowTest.php
  - tests/Unit/Workflow/QuotationLifecycleStep3Test.php
  - tests/Unit/Workflow/OperationalCommercialGuardrailsTest.php
  - tests/Unit/Workflow/QuotationFollowUpWorkflowTest.php
  - tests/Unit/Workflow/QuotationRevisionLockStep5Test.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - added final logical quotation statuses: `need_validation`, `revision_requested`, `need_revalidation`, `approved`, `converted_to_booking`, and `rejected`.
  - kept legacy statuses readable through the normalizer: `pending_validation`, `pending_revalidation`, `customer_approved`, and `booking_created`.
  - changed central status sync output to final `need_validation` / `need_revalidation`.
  - changed customer approval output to final `approved`.
  - changed booking conversion output to final `converted_to_booking`.
  - added `php artisan quotations:normalize-status` with safe dry-run/apply modes.
  - stopped quotation pages from silently mutating legacy status data during page loads.
  - normalized quotation index metrics, status filters, detail workflow fallback, Reservation dashboard KPI counts, and Manager dashboard status counts.
  - marked inactive namespaced quotation validation/revision services as deprecated compatibility services rather than deleting them.
- Mandatory audit result:
  - multi-language: pass, UI labels changed through existing `ui_phrase()` calls.
  - multi-currency: N/A, no price/currency logic changed.
  - dark/light mode: pass, no theme class changes.

## 2026-06-09 (Quotation Status Flow Phase 2)
- Scope: centralize quotation action decisions and tighten sent/response/booking workflow without renaming database statuses.
- Updated files:
  - app/Support/Workflow/QuotationStatusNormalizer.php
  - app/Support/QuotationActionResolver.php
  - app/Services/Quotation/QuotationWorkflowService.php
  - app/Services/Quotation/QuotationFollowUpAutomationService.php
  - app/Services/Quotation/QuotationCustomerResponseService.php
  - app/Services/Booking/BookingFromQuotationService.php
  - app/Http/Controllers/BookingController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Models/QuotationFollowUp.php
  - tests/Unit/Support/QuotationActionResolverTest.php
  - tests/Unit/Workflow/QuotationFollowUpWorkflowTest.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - action resolver now branches from `QuotationStatusNormalizer::normalize()` so UI actions align with logical statuses.
  - `sent` quotations show follow-up and customer response actions, not direct quotation revision.
  - customer-requested revision remains routed through customer response and moves the quotation to `under_revision`.
  - approved quotations focus on `Create Booking` and PDF preview.
  - Mark as Sent keeps `last_sent_at`, writes optional send metadata/logs when schema exists, and schedules the first follow-up for H+3.
  - initial `quotation_sent` follow-up rows are created once per quotation to prevent duplicate automation records.
  - booking guards now require logical approved quotation status and accept both `valid` and `validated` validation status values.
  - quotation detail workflow card shows logical Current Status and pending validation count.
- Mandatory audit result:
  - multi-language: pass, new UI-facing labels use `ui_phrase()` where rendered.
  - multi-currency: N/A, no money calculation changed.
  - dark/light mode: pass, no theme class changes.

## 2026-06-09 (Quotation Status Flow Phase 1)
- Scope: add a central status sync service and compatibility map without renaming existing database statuses.
- Updated files:
  - app/Support/Workflow/QuotationStatusNormalizer.php
  - app/Support/Workflow/QuotationWorkflow.php
  - app/Services/Quotation/QuotationStatusService.php
  - app/Services/Quotation/QuotationWorkflowService.php
  - app/Services/QuotationValidationService.php
  - app/Services/Quotation/QuotationValidationService.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Http/Controllers/Sales/QuotationValidationController.php
  - app/Support/QuotationActionResolver.php
  - tests/Unit/Workflow/QuotationStatusServiceTest.php
  - tests/Unit/Workflow/QuotationWorkflowServicePhase5Test.php
  - tests/Unit/Workflow/QuotationWorkflowTest.php
  - tests/Unit/Workflow/QuotationLifecycleStep3Test.php
  - tests/Unit/Workflow/QuotationRevisionLockStep5Test.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - added logical compatibility mapping: `pending_validation -> need_validation`, `pending_revalidation -> need_revalidation`, `customer_approved -> approved`, `booking_created -> converted_to_booking`.
  - added `QuotationStatusService` as the central sync point for required item counts, validation progress, quotation validation status, and validation-gated quotation status.
  - kept DB status writes compatible with the current project by using existing `pending_validation` and `pending_revalidation` values.
  - blocked workflow transitions to `ready_to_send` and `sent` when validation-required items are not 100% complete.
  - required Mark as Sent to start from logical `ready_to_send` instead of silently jumping from validation states.
  - integrated status sync after validation progress changes and quotation save/revision sync points.
  - kept existing services in place and wrapped/integrated them instead of deleting legacy flow code.
- Mandatory audit result:
  - multi-language: N/A, no visible UI copy changed.
  - multi-currency: N/A, no money logic changed.
  - dark/light mode: N/A, no visual classes changed.

## 2026-06-09 (Quotation Under Revision Action Priority)
- Scope: ensure quotations with `under_revision` status always show the revision action.
- Updated files:
  - app/Support/QuotationActionResolver.php
  - tests/Unit/Support/QuotationActionResolverTest.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - prioritized `under_revision` / revision-requested action resolution before validation and ready-to-send branches.
  - kept `Revise Quotation` pointing to the quotation edit/revision form.
  - added test coverage for `under_revision` with both pending and valid validation statuses.
- Mandatory audit result:
  - multi-language: pass, reused existing action label.
  - multi-currency: N/A.
  - dark/light mode: N/A.

## 2026-06-09 (Quotation Validation-Gated Ready To Send Flow)
- Scope: enforce quotation lifecycle so `ready_to_send` and `sent` are available only after item/service validation reaches 100%.
- Updated files:
  - app/Models/Quotation.php
  - app/Services/Quotation/QuotationWorkflowService.php
  - app/Http/Controllers/Sales/QuotationValidationController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Support/QuotationActionResolver.php
  - tests/Unit/Workflow/QuotationWorkflowServicePhase5Test.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - added first-class quotation constants/options for `ready_to_send`, `under_revision`, and `pending_revalidation`.
  - guarded workflow transitions so `ready_to_send` and `sent` are rejected when validation-required items are not fully validated.
  - changed validation finalization to move fully validated quotations to `ready_to_send`.
  - changed Mark as Sent to pass through `ready_to_send` before transitioning to `sent`.
  - synchronized quotation status after save/revision: incomplete saves move to `pending_validation`/`pending_revalidation`, complete saves move to `ready_to_send`.
  - kept revision validation focused on new validation-required items by preserving validated state for existing items.
  - updated action resolution so fully validated pending quotations show send actions instead of validation actions.
- Mandatory audit result:
  - multi-language: pass, no new visible UI strings beyond existing action/status labels.
  - multi-currency: N/A, no money calculation or display changed.
  - dark/light mode: pass, no visual theme classes changed.

## 2026-06-09 (Quotation Creator Revision Validation Carry-over)
- Scope: make quotation revisions editable by the quotation creator while preserving validated service item state.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Policies/QuotationPolicy.php
  - app/Support/QuotationActionResolver.php
  - resources/views/modules/quotations/_form.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - allowed the quotation creator to manage their own quotation when they have quotation update permission.
  - changed locked-stage revision actions to open the quotation edit/revision form instead of forcing itinerary revision.
  - submitted existing `quotation_items.id` from quotation item rows so validated item state can be carried over reliably.
  - kept validated service item metadata during update/revision when the submitted row belongs to an already validated quotation item.
  - left newly added service rows without an existing item id so they are treated as new items and re-enter validation when required.
- Mandatory audit result:
  - multi-language: pass, reused existing `ui_phrase()` labels in the touched form/action surfaces.
  - multi-currency: pass, no currency conversion logic changed.
  - dark/light mode: pass, no visual theme classes changed.

## 2026-06-09 (Quotation Edit Inquiry Lock)
- Scope: prevent quotation edit/revision from changing its linked inquiry.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/quotations/_form.blade.php
  - docs/standards/quotation-standard.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - locked the Inquiry field on edit/revision forms while preserving the hidden `inquiry_id` submitted to the backend.
  - stopped edit/revision updates from re-validating the existing inquiry as a new quotation-generation inquiry.
  - allowed already-linked final inquiries to remain valid for editing/revising their quotation.
  - rejected attempts to change `inquiry_id` after a quotation has been created.
- Mandatory audit result:
  - multi-language: pass, new validation message uses `ui_phrase()`.
  - multi-currency: N/A, no money display changed.
  - dark/light mode: pass, no visual theme classes changed.

## 2026-06-08 (Quotation Detail Card Inquiry Field)
- Scope: clean up Quotation detail card metadata.
- Updated files:
  - resources/views/modules/quotations/show.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - added Inquiry field showing linked inquiry number in the Quotation detail card.
  - moved Inquiry before Source Itinerary so inquiry context is read first.
  - added inquiry Customer Name using `(customer code) customer name` format.
  - added inquiry Deadline Date and Notes to the Quotation detail card.
  - removed the header-level Itinerary display so the card keeps only one itinerary source field.
  - removed duplicate lower Itinerary field from the same card.
  - removed Break Time metadata from the Quotation detail card because it is itinerary-specific context.
  - changed the sidebar inquiry/itinerary card into an Inquiry-only card using `quotation.inquiry` as the source.
- Mandatory audit result:
  - multi-language: pass, reused existing `ui_phrase('Inquiry')`.
  - multi-currency: N/A, no money display changed.
  - dark/light mode: pass, reused existing detail card classes.

## 2026-06-08 (Reservation Dashboard Modernization)
- Scope: refactor Reservation dashboard into a focused operational dashboard for assigned work.
- Updated files:
  - app/Http/Controllers/Reservation/DashboardController.php
  - resources/views/reservation/dashboard.blade.php
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - scoped Reservation dashboard metrics to inquiries handled/assigned to the current Reservation user, with created-by fallback only for legacy schemas without assignment columns.
  - replaced broad/global booking metrics with relevant operational KPIs: deadline watch, unquoted inquiries, ready-to-book quotations, and trips in the next 14 days.
  - added modern KPI cards with consistent icon, card, and tone patterns.
  - added Reservation Funnel diagram to show movement from assigned inquiry through itinerary, quotation, booking readiness, and upcoming trips.
  - added Quotation Workbench horizontal bars for draft, pending validation, sent, and ready-to-book states.
  - added Trips Next 14 Days bar chart using stable responsive HTML/CSS rather than a new chart dependency.
  - added action-focused side panels for deadline watch, ready-to-book quotations, and upcoming trips.
  - added assigned inquiry board for quick follow-up scanning.
- Mandatory audit result:
  - multi-language: pass, all visible labels use `ui_phrase()`.
  - multi-currency: pass, booked value and quotation value use existing money components.
  - dark/light mode: pass, reused app dashboard/card/status classes and dark variants.

## 2026-06-08 (Current User Display Alias)
- Scope: standardize user-name rendering for audit and assignment fields.
- Updated files:
  - app/Support/helpers.php
  - resources/views/components/masked-user-name.blade.php
  - resources/views/partials/_audit-info.blade.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/inquiries/show.blade.php
  - resources/views/modules/inquiries/_form.blade.php
  - resources/views/modules/quotations/create.blade.php
  - resources/views/modules/quotations/edit.blade.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/quotations/show.blade.php
  - resources/views/modules/itineraries/_form.blade.php
  - resources/views/modules/currencies/edit.blade.php
  - resources/views/modules/bookings/partials/_services-workspace.blade.php
  - resources/views/editor/dashboard.blade.php
  - resources/views/editor/manual-item-queue.blade.php
  - resources/views/manager/dashboard.blade.php
- Applied updates:
  - added `ui_user_name()` helper to render the current authenticated user as `You`.
  - kept other users rendered by their real name.
  - applied the helper to Created By, Assigned/Handled By, Updated By, and related audit/history labels in the touched views.
  - updated `x-masked-user-name` so current user resolves to `You` while preserving masking behavior for other users.
- Mandatory audit result:
  - multi-language: pass, reused existing `ui_phrase('You')`.
  - multi-currency: N/A, no money display changed.
  - dark/light mode: pass, text rendering only.

## 2026-06-08 (Inquiry Deadline Reminder Priority Window)
- Scope: make inquiry deadline reminders in top navigation follow priority-specific windows before quotation is created.
- Updated files:
  - app/Support/InquiryDeadlineReminder.php
  - app/Http/Controllers/Sales/InquiryController.php
  - app/Http/View/SidebarComposer.php
  - resources/views/layouts/master.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hans/ui_core.php
  - lang/zh_Hant/ui_core.php
  - docs/standards/inquiry-standard.md
- Applied updates:
  - added shared `InquiryDeadlineReminder` query helper for both initial navbar count and polling endpoint.
  - low priority reminders appear from H-1 through H-0.
  - normal/medium priority reminders appear from H-2 through H-0.
  - high priority reminders appear from H-7 through H-0.
  - reminders only count inquiries assigned/handled by current user and not connected to quotation.
  - inquiry creator is excluded unless they are also the assigned/handled user.
  - browser notification text now includes deadline label and priority instead of hard-coded H-1 wording.
- Mandatory audit result:
  - multi-language: pass, added new navbar notification phrases to locale dictionaries.
  - multi-currency: N/A, no money display changed.
  - dark/light mode: pass, reused existing top navigation bell styling.

## 2026-06-08 (Inquiry Quotation One-to-One Relationship)
- Scope: enforce Inquiry and Quotation as a one-to-one relationship across model, create/edit flow, inquiry UI, and database constraint.
- Updated files:
  - app/Models/Inquiry.php
  - app/Http/Controllers/Sales/InquiryController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Http/View/SidebarComposer.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/inquiries/show.blade.php
  - resources/views/modules/quotations/_form.blade.php
  - database/migrations/2026_06_08_000000_enforce_one_to_one_inquiry_quotation.php
  - docs/standards/inquiry-standard.md
  - docs/standards/quotation-standard.md
  - docs/blueprint/VOYEX_STATUS_MATRIX.md
  - docs/standards/README.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - made `Inquiry::quotation()` the canonical one-to-one relation.
  - kept `Inquiry::quotations()` only as backward-compatible collection relation that should contain at most one row.
  - changed quotation inquiry availability to exclude inquiries that already have any quotation.
  - included soft-deleted quotations in one-to-one checks so deleted rows still reserve their inquiry relationship.
  - allowed quotation edit to keep its own inquiry selected while blocking switch to an inquiry owned by another quotation.
  - changed server-side quotation create/edit validation to reject inquiries already linked to another quotation.
  - updated inquiry index/detail to render a single linked quotation and hide Generate Quotation when one already exists.
  - refined inquiries index Quotation column to show `Quotation number | Order Number` with quotation status badge, or `-` when no quotation exists.
  - added unique index migration for `quotations.inquiry_id`, with a duplicate legacy-link guard before the index is created.
- Mandatory audit result:
  - multi-language: pass, reused existing `ui_phrase()` labels/messages.
  - multi-currency: N/A, no monetary calculation changed.
  - dark/light mode: pass, existing inquiry and quotation UI classes preserved.

## 2026-06-08 (Inquiry Edit Lock When Quotation Exists)
- Scope: lock Inquiry edit actions once an inquiry is connected to any quotation.
- Updated files:
  - app/Models/Inquiry.php
  - app/Http/Controllers/Sales/InquiryController.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/inquiries/show.blade.php
  - docs/standards/inquiry-standard.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - added reusable `Inquiry::hasLinkedQuotation()` helper.
  - changed backend edit/update guard so any linked quotation locks inquiry editing, regardless of quotation status.
  - hid Edit Inquiry actions on inquiry detail, desktop index action dropdown, and mobile index cards when a quotation exists.
- Mandatory audit result:
  - multi-language: pass, reused existing `ui_phrase()` messages and labels.
  - multi-currency: N/A, no money display changed.
  - dark/light mode: pass, existing action styles preserved.

## 2026-06-08 (Quotation Create/Edit Item Editing Refactor)
- Scope: refactor Quotation create/edit item management so generated and manually added service items can be edited, added, and removed consistently.
- Updated files:
  - resources/views/modules/quotations/_form.blade.php
  - docs/standards/quotation-standard.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied updates:
  - unified generated itinerary items and Add Service items under one editable quotation item row template.
  - made item description, day, quantity, and rate editable for generated and service-catalog items.
  - added remove action to generated itinerary rows and Add Service rows.
  - added drag-and-drop movement for quotation service items across available Day groups in create/edit forms.
  - kept item `day_number` synchronized when users drop an item into another Day group or change Day manually.
  - decoupled Inquiry and Itinerary selectors so selecting/generating from an itinerary no longer clears or overwrites the selected inquiry.
  - relaxed backend quotation validation so user-selected inquiry is accepted independently from the selected itinerary reference.
  - standardized generated and Add Service item descriptions to `Service Type: Service name`, with hotel rows using `Hotel: Hotel Name - Room Name`.
  - removed the visible Day column/input from quotation item rows because items are already grouped by Day.
  - aligned quotation item row controls, drag handles, and remove buttons to a consistent top-aligned 42px control height.
  - changed only drag handles and remove actions into square icon-only controls while preserving the standard one-item-per-row grid layout.
  - aligned quotation item row column spans and horizontal padding with the item header so Description, Qty, Rate, and Unit Price widths match.
  - aligned Add Service controls into one row with the Add Service button on the far right at the same 42px control height as inputs.
  - removed legacy JavaScript helpers for unused markup/discount display controls, pax badges, and option visibility resets.
  - throttled item total recalculation through `requestAnimationFrame` during input typing to reduce repeated recalculation work.
  - converted remaining quotation form money-input labels and manual remove actions to `ui_phrase()` for multi-language consistency.
  - removed Adult/Child Publish Rate badges from quotation item rows because pax type metadata is not required in the item list UI.
  - allowed Add Service while an itinerary is selected, so users can extend generated quotations without switching to manual-only mode.
  - removed obsolete manual-only service item template/drag-drop code paths.
  - added real confirmation before Generate replaces existing generated/service item rows.
  - localized the Generate button label through `ui_phrase()`.
- Mandatory audit result:
  - multi-language: pass, reused existing UI phrases (`Generate`, `Remove`, `Day`, `Rate`) through `ui_phrase()`.
  - multi-currency: pass, item rate/final amount still convert display currency to IDR before submit.
  - dark/light mode: pass, reused existing `app-input`, `btn-outline-sm`, and `btn-danger-sm` classes.

## 2026-06-04 (Itineraries Index, Review Connector, and Detail Map Focus)
- Scope: refine Itineraries quotation action, review connector copy, and detail itinerary map interactions.
- Updated files:
  - resources/views/modules/itineraries/index.blade.php
  - resources/views/modules/itineraries/_form.blade.php
  - resources/views/modules/itineraries/show.blade.php
  - lang/en/itinerary_form.php
  - lang/zh_Hans/itinerary_form.php
  - lang/zh_Hant/itinerary_form.php
- Applied updates:
  - renamed the Item List popover quotation action from `Create Quotation from Itinerary` to `Generate Quotation`.
  - kept the action target as `quotations.create` with `itinerary_id` so the quotation create form opens with the selected itinerary prefilled.
  - restricted action visibility to users who:
    - have quotation module access,
    - have one of the allowed roles: Reservation, Manager, or Director,
    - are the creator of the itinerary.
  - applied the same rule to desktop table and mobile card Item List popovers.
  - fixed create/edit itinerary Review tab connector text when a Break Time row sits between items:
    - Break Time is treated as rest time, not a travel target.
    - connector target now skips break rows and points to the next real schedule item, or end point when no next item exists.
    - review connector text is now rendered through `itinerary_form.review.connector_time_to`.
  - optimized Itinerary detail map focus behavior:
    - Schedule by Day cards reuse `data-map-focus-key` to focus related Leaflet markers.
    - marker and route layers are rendered only when the active day changes or when the map has not been rendered yet.
    - repeated schedule clicks now use the in-memory marker registry instead of rebuilding all markers/routes.
    - OSRM route geometry is cached per coordinate pair to avoid duplicate route fetches during rerenders.
    - map resize handling is debounced and zoom/pan no longer forces a full itinerary layer rebuild.
- Mandatory audit result:
  - multi-language: pass, reused existing `ui_phrase('Generate Quotation')` phrase and added itinerary review connector phrase to active locale dictionaries.
  - multi-currency: N/A, no monetary rendering changed.
  - dark/light mode: pass, existing button and popover classes preserved.

## 2026-05-28 (Itinerary Detail + Day Planner + PDF Timeline Stabilization)
- Scope: stabilize itinerary detail UX, break time flow, map marker behavior, connector validation, and itinerary PDF timeline rendering.
- Updated files:
  - resources/views/modules/itineraries/show.blade.php
  - resources/views/modules/itineraries/_form.blade.php
  - app/Http/Controllers/Admin/ItineraryController.php
  - resources/views/pdf/itinerary.blade.php
  - lang/en/itinerary_form.php
  - lang/zh_Hans/itinerary_form.php
- Applied updates:
  - fixed itinerary detail runtime issues and header action consistency:
    - resolved undefined variable problems in itinerary detail flow (including previous `$today` issue),
    - standardized header actions and role-gated PDF generation visibility.
  - standardized itinerary detail schedule/tabs and layout:
    - preserved tabs while removing inconsistent "Display By Day" label,
    - enforced shared tab styling consistency with `app-tabs/app-tab`,
    - improved card composition (itinerary detail + hotels + schedule hierarchy).
  - implemented break time as first-class day planner entity:
    - break row is standalone (not item subtype), supports drag/drop placement between items,
    - only one break per day rule preserved in planner behavior,
    - break row visual refactor: neutral style, single-line controls, no highlight/number dependency.
  - persisted break time end-to-end:
    - break start/end stored in `itinerary_day_points`,
    - break data restored on edit/create and rendered consistently in review/detail contexts.
  - strengthened day planner completeness and navigation guard:
    - day status becomes `Incomplete` when required connector values are empty,
    - `Next` action is blocked until connector requirements are fulfilled,
    - invalid fields now auto-focus with contextual feedback.
  - added warning UX for empty connectors:
    - connector inputs receive warning background/border state when empty,
    - inline reminder text shown at connector level (right-side helper),
    - warning behavior works for both start connector and per-row connectors.
  - upgraded validation guidance UX:
    - on validation fail, view auto-navigates to offending Day,
    - offending field/container blinks several times to emphasize required completion,
    - validation notice banner also blinks for stronger user attention.
  - map behavior improvements for break/item visualization:
    - added break marker support with dedicated icon/popup payload,
    - anti-overlap marker strategy for colliding points,
    - refined break marker anchoring relative to associated item marker and stabilized heavy render loops.
  - itinerary PDF timeline refactor (connector + break rows):
    - controller now builds ordered `pdf_rows` timeline (`item`, `break`, `connector`) per day,
    - connector and break rows rendered as merged rows (all columns except `No`),
    - connector row format simplified to:
      - `X min - Estimated travel time to <next item>`,
    - removed duplicate connector duration output,
    - break row format standardized to:
      - `<duration> - Break Time <start> - <end>`.
  - i18n updates:
    - added itinerary form validation keys for connector guidance:
      - `itinerary_form.validation.connector_incomplete`
      - `itinerary_form.validation.connector_estimate_hint`
    - wired day planner warning/validation text to translation dictionaries (EN + zh_Hans).
- Validation run:
  - syntax checks passed for touched controller/views/lang files via `php -l`.

## 2026-05-28 (Global Tab Style Standardization)
- Scope: standardize tab visual system for consistent UI behavior across modules.
- Updated files:
  - resources/css/app.css
  - resources/views/modules/itineraries/show.blade.php
  - resources/views/components/module-status-tabs.blade.php
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
- Applied updates:
  - introduced reusable global tab classes: `app-tabs` and `app-tab` (+ `is-active` state).
  - migrated itinerary detail "Schedule by Day" tabs to the new shared style.
  - migrated shared `module-status-tabs` component to use the same standardized tab classes.
  - documented mandatory tab class usage in UI component guide for future module implementation.

## 2026-05-22 (Role & Permissions + User Manager Standardization)
- Scope: standardize filter/UI patterns for Role Manager and User Manager index pages and enforce mandatory audits.
- Updated files:
  - resources/views/modules/roles/index.blade.php
  - resources/views/modules/roles/partials/_index-results.blade.php
  - resources/views/modules/users/index.blade.php
  - app/Http/Controllers/Admin/RoleController.php
  - app/Http/Controllers/Admin/UserController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - refactored filter cards to compact style (removed filter title/description blocks).
  - standardized filter layout and reset style:
    - responsive grid alignment with `lg` spans,
    - reset action as `btn-secondary`, `h-[42px]`, matching input radius.
  - applied text-search minimum 3-character guard on backend query flow (`search`).
  - standardized desktop actions to `x-ui.table-action-dropdown` on both modules.
  - improved row numbering to respect pagination offsets.
  - migrated role/user success and error responses in controller flow to `ui_phrase(...)`.
- Mandatory audit result:
  - multi-language: user-facing text/messages in changed scope follow translation path conventions.
  - multi-currency: no currency rendering in these index pages (N/A, no regression).
  - dark/light mode: updated filter/action styles remain theme-compatible.

## 2026-05-22 (Currencies Index Filter/UI Standardization + Audit)
- Scope: standardize Currencies index filter/UI to the mandatory baseline and enforce audit/optimization rules.
- Updated files:
  - resources/views/modules/currencies/index.blade.php
  - app/Http/Controllers/Admin/CurrencyController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - refactored filter into one compact card without filter title/description.
  - standardized filter controls and behavior:
    - search text (`q`) with min 3-character guard (frontend + backend),
    - status (`active` / `inactive`),
    - per_page (10/25/50/100),
    - reset action.
  - aligned reset action to standard (`btn-secondary`, `h-[42px]`, matching input radius).
  - standardized desktop actions to `x-ui.table-action-dropdown`.
  - standardized status badges to `x-ui.status-badge` (desktop + mobile).
  - improved row numbering to respect pagination offsets.
  - standardized backend response/error text with `ui_phrase(...)` for multilingual consistency.
- Mandatory audit result:
  - multi-language: user-facing strings in updated scope follow translation path (`ui_phrase(...)`).
  - multi-currency: currency module calculations/rendering kept intact; no logic regression introduced.
  - dark/light mode: updated filter/table/action classes remain theme-compatible.

## 2026-05-22 (Action Column Standardization for 6 Service Modules)
- Scope: standardize desktop table action interactions to the locked dropdown action pattern.
- Updated files:
  - resources/views/modules/activities/partials/_index-results.blade.php
  - resources/views/modules/island-transfers/index.blade.php
  - resources/views/modules/food-beverages/index.blade.php
  - resources/views/modules/hotels/partials/_index-results.blade.php
  - resources/views/modules/transports/index.blade.php
  - resources/views/modules/tourist-attractions/partials/_index-results.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - replaced inline multi-button desktop actions with `x-ui.table-action-dropdown` on all six module tables.
  - standardized action item visual structure:
    - neutral actions (`View`/`Detail`/`Edit`/`Copy`/`Duplicate`) as list rows,
    - state toggle action with semantic tone (activate/deactivate),
    - destructive delete action (where available) separated with divider.
  - preserved existing action routes and confirmation behavior.
- Audit result:
  - multi-language: action labels in changed scope remain translation-driven via `ui_phrase(...)`.
  - dark/light mode: dropdown action styling follows existing dark/light-safe utility classes.
  - multi-currency: no currency logic changed in this action-only standardization scope.

## 2026-05-22 (Batch Standardization: Activities, Island Transfers, F&B, Hotels, Transports, Tourist Attractions)
- Scope: standardize index filter/UI patterns across six service modules and enforce mandatory audit gates.
- Updated files:
  - resources/views/modules/activities/index.blade.php
  - resources/views/modules/activities/partials/_index-results.blade.php
  - resources/views/modules/island-transfers/index.blade.php
  - resources/views/modules/food-beverages/index.blade.php
  - resources/views/modules/hotels/index.blade.php
  - resources/views/modules/hotels/partials/_index-results.blade.php
  - resources/views/modules/transports/index.blade.php
  - resources/views/modules/tourist-attractions/index.blade.php
  - resources/views/modules/tourist-attractions/partials/_index-results.blade.php
  - app/Http/Controllers/Admin/ActivityController.php
  - app/Http/Controllers/Admin/IslandTransferController.php
  - app/Http/Controllers/Admin/FoodBeverageController.php
  - app/Http/Controllers/Admin/HotelController.php
  - app/Http/Controllers/Admin/TransportController.php
  - app/Http/Controllers/Admin/TouristAttractionController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - standardized filter cards to compact style (no filter title/description blocks).
  - standardized reset action across modules (`btn-secondary`, `h-[42px]`, matching input radius).
  - added/normalized text-search input (`q`) where needed with minimum 3-character frontend guard.
  - added/normalized status filter (`active` / `inactive`) on modules that previously did not expose it.
  - aligned filter form grids to consistent responsive pattern (`sm` + `lg` spans).
  - standardized status badge usage to `x-ui.status-badge` in touched index result views.
  - synchronized backend filtering:
    - validation for `status` where newly introduced,
    - minimum 3-character guard for text search on index queries,
    - active/inactive filtering via soft-delete aware conditions.
  - migrated several hardcoded success messages in controllers to `ui_phrase(...)` for multilingual consistency.
- Mandatory audit result:
  - multi-language: filter labels/messages in changed scope moved to translation path conventions.
  - multi-currency: currency rendering remains intact (notably existing service-rate outputs); no regression introduced.
  - dark/light mode: updated classes/components remain theme-compatible.

## 2026-05-22 (Vendors/Providers Index UI + Filter Standardization)
- Scope: standardize Vendors/Providers index filter and UI to mandatory baseline and run required audits.
- Updated files:
  - resources/views/modules/vendors/index.blade.php
  - app/Http/Controllers/Admin/VendorController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - migrated page layout to standard `module-grid-9-3` with `module-grid-side` + `module-grid-main`.
  - refactored filter to single compact card (no filter title/description).
  - removed non-standard filter actions (`Apply`, quick status tabs).
  - standardized active filters with AJAX contract:
    - search text (`q`) with min 3 characters,
    - service type,
    - status (`active` / `inactive`),
    - per_page (10/25/50/100),
    - reset action.
  - standardized reset styling (`btn-secondary`, `h-[42px]`, matching input radius).
  - refined table typography/colors for consistent light/dark readability.
  - standardized row actions with dropdown visual contract and semantic danger/activate tone.
  - synced backend filter behavior:
    - added validation for `status` and `service_type`,
    - added min 3-character guard for `q`,
    - removed dependency on `location` query input from index filter flow.
  - wrapped vendor success/error responses with `ui_phrase(...)` for i18n compliance.
- Mandatory audit result:
  - multi-language: user-facing UI strings and responses in this scope follow translation path.
  - multi-currency: vendors index does not display currency values (N/A, no regression).
  - dark/light mode: updated classes/components remain theme-compatible.

## 2026-05-22 (Destinations Index Filter Standardization + Audit + Refactor)
- Scope: standardize Destinations index filter/UI to the mandatory baseline and enforce audit gates.
- Updated files:
  - resources/views/modules/destinations/index.blade.php
  - app/Http/Controllers/Admin/DestinationController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - refactored filter area into one compact card without filter title/description.
  - standardized filter controls with AJAX contract:
    - search text (`q`) with min 3 characters,
    - status (`active` / `inactive`),
    - per_page (10/25/50/100),
    - reset action.
  - aligned reset action style to standard (`btn-secondary`, `h-[42px]`, matching radius).
  - migrated desktop table wrapper to `x-ui.data-table`.
  - standardized status badges to `x-ui.status-badge`.
  - standardized empty states using `x-ui.empty-state` (desktop and mobile).
  - improved row numbering to respect pagination offset.
  - standardized desktop action interactions with `x-ui.table-action-dropdown`.
  - converted linked-data labels into translatable text (`Vendors`, `Hotels`, `Attractions`, `Airports`).
  - synced backend filter behavior:
    - added `status` validation and query handling,
    - added minimum 3-character guard for search query.
  - wrapped destination success flash messages with `ui_phrase(...)` for multilingual compliance.
- Mandatory audit result:
  - multi-language: user-facing text in scope now follows translation path (`ui_phrase(...)`).
  - multi-currency: destination index does not render currency values in current scope (N/A, no regression).
  - dark/light mode: updated components/classes remain theme-compatible.

## 2026-05-22 (Airports Index Filter Standardization + Audit + Refactor)
- Scope: standardize Airports index filter/UI to the mandatory baseline and enforce audit gates.
- Updated files:
  - resources/views/modules/airports/index.blade.php
  - app/Http/Controllers/Admin/AirportController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - refactored filter into one compact card without filter title/description.
  - standardized filter controls with AJAX contract:
    - search text (`q`) with min 3 characters,
    - status (`active` / `inactive`),
    - per_page (10/25/50/100),
    - reset action.
  - aligned reset action style to standard (`btn-secondary`, `h-[42px]`, matching radius).
  - migrated desktop table wrapper to `x-ui.data-table`.
  - standardized status badges to `x-ui.status-badge`.
  - standardized empty states using `x-ui.empty-state` (desktop and mobile).
  - improved pagination row numbering to respect current page offsets.
  - standardized desktop row action UI with `x-ui.table-action-dropdown`.
  - synced backend filter behavior:
    - added `status` validation and query handling,
    - added minimum 3-character guard for text search.
  - wrapped Airport success flash messages with `ui_phrase(...)` for multilingual compliance.
- Mandatory audit result:
  - multi-language: user-facing strings in this scope now use translation path (`ui_phrase(...)`).
  - multi-currency: airport index has no currency rendering in scope (N/A, no regression).
  - dark/light mode: updated components/classes remain theme-compatible.

## 2026-05-22 (Invoices Layout Grid 9:3 Standardization)
- Scope: align Invoice index page structure to mandatory layout standard (`module-grid-9-3`).
- Updated files:
  - resources/views/modules/invoices/index.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Revisions applied:
  - wrapped invoice index content in `module-grid-9-3`.
  - added standard sidebar container (`module-grid-side`) using reusable `components.module-index-sidebar-info`.
  - moved metrics/filter/table/pagination into `module-grid-main` to maintain consistent module layout behavior.
  - preserved existing filter standard, AJAX behavior, i18n/multi-currency rendering, and dark/light compatibility.

## 2026-05-22 (Invoices Filter Simplification Follow-up)
- Scope: simplify Invoice index filters to only essential controls and align grid layout with locked standard.
- Updated files:
  - resources/views/modules/invoices/index.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Revisions applied:
  - removed `invoice_from` and `invoice_to` filter inputs from Invoice index UI.
  - retained only:
    - search text,
    - invoice type,
    - status,
    - per-page (10/25/50/100),
    - reset action.
  - adjusted filter grid layout to standard compact composition (`lg:grid-cols-3` with aligned reset row span).
  - preserved AJAX filter behavior and standard reset styling.

## 2026-05-22 (Invoices Index Standardization + Audit + Optimization)
- Scope: standardize Invoice index UI/filter to the locked baseline and enforce mandatory audit rules.
- Updated files:
  - resources/views/modules/invoices/index.blade.php
  - app/Http/Controllers/Finance/InvoiceController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - refactored filter area into a single compact card (`app-card`) without filter title/description.
  - removed `Apply` button and `Quick Status` tabs from invoice filter area.
  - aligned active filters to compact standard with AJAX contract:
    - `q` (text),
    - `invoice_type`,
    - `status`,
    - `invoice_from`,
    - `invoice_to`,
    - `per_page` (10/25/50/100),
    - `Reset` action.
  - enabled AJAX filter/pagination hooks (`data-service-filter-*`) on Invoice index page.
  - standardized `Reset` action style (`btn-secondary`, `h-[42px]`, matching input radius).
  - tightened backend text search behavior with minimum 3-character guard for `q`.
  - grouped search `OR` conditions inside nested `where(...)` to keep query logic stable.
  - refined table visual consistency for light/dark mode (header/body text tones + hover states).
- Mandatory audit result:
  - multi-language: user-facing labels and messages remain wrapped via `ui_phrase(...)`.
  - multi-currency: monetary columns and summary continue using `x-ui.money`.
  - dark/light mode: updated filter/table styling remains theme-compatible.

## 2026-05-22 (Bookings Index Filter Standardization + Mandatory Audit Compliance)
- Scope: standardize Bookings index filters to the locked Customers/Agents baseline and enforce mandatory audit gates.
- Updated files:
  - resources/views/modules/bookings/index.blade.php
  - app/Http/Controllers/BookingController.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Applied updates:
  - converted Bookings filter area into one compact single card (`app-card`) with no filter title/description.
  - removed `Apply` button and `Quick Status` tab group from filter area.
  - retained only operationally needed filter inputs:
    - order number (text),
    - quotation (text),
    - status,
    - per-page (10/25/50/100),
    - reset action.
  - aligned `Reset` button to standard (`btn-secondary`, same control height and radius as inputs).
  - preserved AJAX filtering and pagination behavior through existing `data-service-filter-*` contract.
  - enforced text minimum 3-character guard on Bookings backend filter processing for `q`, `quotation`, and `order_number`.
- Mandatory audit result:
  - multi-language: all user-facing filter/table labels remain under `ui_phrase(...)` path.
  - multi-currency: bookings index does not render currency values in current scope (N/A, no regression introduced).
  - dark mode/light mode: updated filter layout uses existing theme-aware utility classes and remains compatible.

## 2026-05-22 (Mandatory Multi-language Audit Rule)
- Scope: governance update to enforce i18n support and audit on every code change.
- Updated files:
  - PROJECT_GUIDELINES.md
  - VOYEX_CRM_AI_GUIDELINE.md
  - docs/I18N_GUIDE.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Policy updates:
  - every update must remain multi-language compliant by default.
  - every update must include mandatory i18n audit for all user-facing text/paragraph/sentences in scope.
  - changes are considered incomplete when i18n audit has not passed.

## 2026-05-22 (Main Index Filter Standard Lock)
- Scope: lock Customers/Agents filter style as the primary mandatory standard for future module index filters.
- Updated files:
  - PROJECT_GUIDELINES.md
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_BLUEPRINT.md
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Standard decision:
  - `customers.index` is the main baseline for index filter style and behavior.
  - filter fields can vary per module business needs, but UI/UX contract is mandatory:
    - single compact filter card,
    - no nested card in filter area,
    - AJAX filter + AJAX pagination (`data-service-filter-*`),
    - text filter minimum 3 characters,
    - `Reset` button secondary style, aligned height with inputs, and matching radius.

## 2026-05-22 (Inquiries Filter Standardization Rollout)
- Scope: align Inquiries index filter with the locked Customers/Agents main standard.
- Updated files:
  - app/Http/Controllers/Sales/InquiryController.php
  - resources/views/modules/inquiries/index.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Applied standard:
  - single compact filter card with only required filters:
    - text search,
    - priority,
    - per_page (10/25/50/100),
    - reset action.
  - removed non-required inquiry index filter UI elements:
    - filter title/description block,
    - quick priority tab buttons,
    - apply submit button.
  - reset action aligned with input height and uses secondary style with matching radius.
  - AJAX filtering and pagination behavior preserved via `data-service-filter-*`.
  - backend index filter scope aligned to active UI filters (`q`, `priority`, `per_page`).
## 2026-05-22 (Customers/Agents Index Filter Standardization)
- Scope: standardize Customers/Agents index as baseline for modern unified filter card + AJAX list behavior.
- Updated files:
  - app/Http/Controllers/Sales/CustomerController.php
  - resources/views/modules/customers/index.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Standardization applied:
  - merged all customers index filters into one compact card (including customer type tabs, keyword, type, status, country, sort, per-page, reset).
  - kept existing AJAX filter engine (`data-service-filter-*`) so filtering and pagination no longer require full-page reload.
  - added text filter minimum-character guard (3 chars) on frontend form contract and backend query guard.
  - added modern sort options (`latest`, `oldest`, `name_asc`, `name_desc`) and status filter (`all`, `active`, `inactive`).
  - improved table row numbering to stay correct across paginated pages.
  - synced new UI phrases to i18n dictionaries (`en`, `zh_Hant`, `zh_Hans`).
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business workflow mutation changes

## 2026-05-22 (Customers/Agents Filter Simplification Revision)
- Scope: refine Customers/Agents filter to show only required controls and remove nested-card layout.
- Updated files:
  - app/Http/Controllers/Sales/CustomerController.php
  - resources/views/modules/customers/index.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Revisions applied:
  - removed filter controls for `status`, `country`, and `latest updated/sort`.
  - removed nested card behavior in filter area by replacing card-based tabs component with inline tab buttons inside the same filter card.
  - aligned backend query logic with simplified filter inputs (no `status` / `country` / `sort` processing on index filter flow).
  - preserved AJAX filter and pagination behavior (no full-page reload).

## 2026-05-22 (Customers/Agents Filter UI Cleanup)
- Scope: final simplification pass for customers filter card visual density.
- Updated files:
  - resources/views/modules/customers/index.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Revisions applied:
  - removed filter card title and description text.
  - removed `All / Type Individual / Type Company` quick-tab buttons because filter type is already covered by `Type` select.
  - preserved existing AJAX filter/pagination behavior and minimum text-character guard.

## 2026-05-22 (Customers/Agents Reset Button Alignment & Style)
- Scope: align reset action control with filter inputs and apply consistent secondary visual style.
- Updated files:
  - resources/views/modules/customers/index.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
  - VOYEX_CRM_SYSTEM_ROADMAP.md
- Revisions applied:
  - adjusted reset action wrapper to explicit form-control height (`42px`) for visual alignment with filter inputs.
  - updated reset button style to `secondary` variant with matching input radius (`var(--app-radius-sm)`).
  - preserved existing AJAX reset behavior through `data-service-filter-reset`.

## 2026-05-21
- Scope: UI documentation only.
- Created files:
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_BLUEPRINT.md
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
  - docs/blueprint/VOYEX_UI_MODULE_LAYOUT_STANDARD.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- No application logic changes.
- No database changes.
- No controller changes.
- No route changes.
- No blade module changes.

## 2026-05-21 (I18N + Currency UI Global Foundation)
- Scope: global UI standard documentation for existing i18n and multi-currency usage.
- Created files:
  - docs/blueprint/VOYEX_I18N_CURRENCY_UI_STANDARD.md
  - resources/views/components/ui/money.blade.php
- Updated files:
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Key decisions:
  - Reuse existing translation stack (`ui_phrase`, `ui_choice`, `ui_token`, `ui_entity`, `ui_action`, plus existing `__`/`trans` contexts).
  - Reuse existing currency stack (`App\Support\Currency`, `x-money`, session currency switcher).
  - Standardize Blade usage with `x-ui.money` wrapper to keep UI refactor consistent.
- No business logic changes.
- No database changes.
- No controller changes.
- No route changes.
- No module-wide blade refactor in this step.

## 2026-05-21 (Reusable UI Components Standardization)
- Scope: standardize shared `components/ui/*` foundation for upcoming module-by-module UI refactor.
- Created components:
  - resources/views/components/ui/page-header.blade.php
  - resources/views/components/ui/status-badge.blade.php
  - resources/views/components/ui/workflow-stepper.blade.php
  - resources/views/components/ui/action-panel.blade.php
  - resources/views/components/ui/empty-state.blade.php
  - resources/views/components/ui/filter-bar.blade.php
  - resources/views/components/ui/data-table.blade.php
  - resources/views/components/ui/metric-card.blade.php
  - resources/views/components/ui/info-card.blade.php
  - resources/views/components/ui/timeline.blade.php
  - resources/views/components/ui/section-card.blade.php
  - resources/views/components/ui/lock-alert.blade.php
  - resources/views/components/ui/date-display.blade.php
  - resources/views/components/ui/module-tabs.blade.php
- Standardized component:
  - resources/views/components/ui/money.blade.php (kept as wrapper to existing `x-money` formatter stack)
- Documentation updated:
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Guardrails maintained:
  - no business logic changes
  - no database changes
  - no controller changes
  - no route changes
- no module page refactor in this step

## 2026-05-21 (Quotations UI Pilot Standardization)
- Scope: UI refactor for `Quotations` module pilot (`index` and `show`) using existing reusable `x-ui.*` components.
- Updated files:
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/quotations/show.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- UI standardization applied:
  - standardized tabs to `x-ui.module-tabs`
  - standardized status badges to `x-ui.status-badge`
  - standardized amount rendering to `x-ui.money`
  - standardized date rendering (selected fields) to `x-ui.date-display`
  - standardized filter wrapper to `x-ui.filter-bar`
  - standardized desktop table wrapper to `x-ui.data-table`
  - added KPI summary cards with `x-ui.metric-card`
  - standardized empty states to `x-ui.empty-state`
  - standardized workflow display to `x-ui.workflow-stepper`
  - standardized lock warning to `x-ui.lock-alert`
  - standardized quick actions to `x-ui.action-panel`
- Guardrails maintained:
  - no database changes
  - no route changes
  - no workflow/business logic transition changes

## 2026-05-21 (Quotation Validation UI Standardization)
- Scope: standardize `Quotation Validation` page UI with existing reusable `x-ui.*` components.
- Updated files:
  - resources/views/modules/quotations/validate.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- UI updates:
  - added `x-ui.page-header` with breadcrumb and back action
  - added `x-ui.workflow-stepper` for quotation lifecycle visibility
  - added `x-ui.lock-alert` for locked validation stage messaging
  - standardized summary area with `x-ui.section-card` + `x-ui.metric-card`
  - standardized validation status badges with `x-ui.status-badge`
  - standardized desktop table wrapper with `x-ui.data-table`
  - standardized no-data handling with `x-ui.empty-state`
  - standardized bulk action area with `x-ui.action-panel`
- i18n/currency hygiene:
  - removed remaining raw meal labels (`Breakfast/Lunch/Dinner`) into `ui_phrase(...)`
  - replaced JS fallback hardcoded error text with translated i18n key usage
  - removed manual `number_format` for hidden money inputs on validation rows
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business logic/status transition changes

## 2026-05-21 (Booking UI Standardization)
- Scope: standardize Booking module UI for index/detail and safe form wrapper updates (create/edit).
- Updated files:
  - resources/views/modules/bookings/index.blade.php
  - resources/views/modules/bookings/show.blade.php
  - resources/views/modules/bookings/create.blade.php
  - resources/views/modules/bookings/edit.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- UI updates:
  - Booking index standardized with `x-ui.metric-card`, `x-ui.filter-bar`, `x-ui.module-tabs`, `x-ui.data-table`, `x-ui.status-badge`, `x-ui.date-display`, `x-ui.empty-state`
  - Booking detail standardized with `x-ui.workflow-stepper`, `x-ui.lock-alert`, `x-ui.action-panel`, `x-ui.status-badge`, `x-ui.date-display`
  - Booking detail monetary displays updated to `x-ui.money` in adjustment + settlement summary sections
  - Booking create/edit page wrappers standardized with `x-ui.section-card`
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business logic/status transition changes

## 2026-05-21 (Booking Reconciliation UI Standardization)
- Scope: standardize dedicated Booking Reconciliation page UI (`bookings/reconciliation`) for actual service confirmation before final invoice.
- Updated files:
  - resources/views/modules/bookings/reconciliation.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- UI updates:
  - standardized header with `x-ui.page-header` + breadcrumb + back action
  - standardized booking workflow visibility with `x-ui.workflow-stepper`
  - standardized lock/cancel/finalized state messaging with `x-ui.lock-alert`
  - added reconciliation summary with `x-ui.metric-card`
  - added booking summary with `x-ui.section-card` + `x-ui.status-badge` + `x-ui.date-display`
  - standardized reconciliation items table with `x-ui.data-table`
  - standardized no-item state with `x-ui.empty-state`
  - standardized monetary display with `x-ui.money` and existing `Currency::format` formatter
  - added adjustment summary and final invoice preview panels with reusable section pattern
  - standardized finalize action area with `x-ui.action-panel`
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business logic/status transition changes

## 2026-05-21 (Voucher UI Standardization)
- Scope: standardize voucher-related UI on booking voucher form and booking detail voucher panel.
- Updated files:
  - resources/views/modules/bookings/voucher-form.blade.php
  - resources/views/modules/bookings/show.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- UI updates:
  - voucher form standardized with `x-ui.page-header`, `x-ui.section-card`, `x-ui.status-badge`, and `x-ui.action-panel`
  - voucher form action/metadata section keeps existing flow but improves consistent component usage
  - booking detail voucher panel uses `x-ui.status-badge` for voucher status per item
  - voucher revision indicator normalized as translated text label (`Revision Rn`)
  - voucher form placeholder `09:00` replaced with translated pattern label (`HH:mm`)
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business logic/status transition changes

## 2026-05-21 (Invoice & Payment UI Standardization)
- Scope: standardize invoice/payment UI layer and booking invoice-payment panel using existing reusable `x-ui.*` components.
- Updated files:
  - app/Http/Controllers/Finance/InvoiceController.php
  - app/Http/Controllers/Finance/PaymentController.php
  - resources/views/modules/invoices/index.blade.php
  - resources/views/modules/invoices/show.blade.php
  - resources/views/modules/invoices/edit.blade.php
  - resources/views/modules/payments/index.blade.php
  - resources/views/modules/payments/create.blade.php
  - resources/views/modules/payments/show.blade.php
  - resources/views/modules/bookings/show.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- UI updates:
  - invoice index: `x-ui.page-header`, KPI cards, filter bar, status tabs, standardized table/empty state/date/money badges
  - invoice detail: workflow stepper, lock alert, summary cards/sections, payment history table, action panel consistency
  - invoice edit: section-card form and action panel, removed manual `number_format` paid display in favor of `x-ui.money`
  - payment index: page header, KPI cards, filter bar, status tabs, standardized table/empty state/date/money badges
  - payment create/show: standardized section cards, invoice impact summary, money/date/status component usage
  - booking detail: invoice/payment summary table with type/status badges, total/paid/balance amounts, and safe payment action links
- Controller adjustment (UI data support only):
  - added lightweight summary aggregates for invoice/payment index pages (count/balance/overdue/status slices)
- Guardrails maintained:
  - no database changes
  - no route changes
  - no core business logic transition changes

## 2026-05-21 (Vendor / Provider UI Standardization)
- Scope: standardize Vendor/Provider master data UI (`index`, `create`, `edit`, and shared form partial).
- Updated files:
  - app/Http/Controllers/Admin/VendorController.php
  - resources/views/modules/vendors/index.blade.php
  - resources/views/modules/vendors/create.blade.php
  - resources/views/modules/vendors/edit.blade.php
  - resources/views/modules/vendors/_form.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- UI updates:
  - vendor index uses `x-ui.page-header`, `x-ui.metric-card`, `x-ui.filter-bar`, `x-ui.module-tabs`, `x-ui.data-table`, `x-ui.status-badge`, `x-ui.empty-state`
  - added summary cards for total/active/inactive/vendors-with-services
  - standardized filter set for keyword, service type, status, location, and per-page
  - vendor create/edit pages standardized with `x-ui.page-header`, `x-ui.section-card`, `x-ui.info-card`, and `x-ui.action-panel`
  - form sections grouped as basic info, address/location, and contact info
- Controller adjustment (UI data support only):
  - added safe filter parameters (`status`, `location`, `service_type`) in index query
  - added lightweight summary aggregates for dashboard cards
  - added `islandTransfers` count in index relation count for service visibility
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business logic/status toggle flow changes

## 2026-05-21 (Vendor UI Cleanup)
- Scope: cleanup vendor index header hierarchy and readability after UI standardization.
- Updated files:
  - resources/views/modules/vendors/index.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Vendor UI cleanup:
  - removed duplicate lower header
  - kept breadcrumb page header as main header
  - moved Add Vendor action to main page header
  - improved KPI labels
  - improved filter labels
  - improved service count display
  - preserved business logic
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business logic changes

## 2026-05-21 (Index UI Cleanup)
- Scope: restore compact index layout standard on refactored modules (`vendors`, `quotations`, `bookings`, `invoices`, `payments`).
- Updated files:
  - resources/views/modules/vendors/index.blade.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/bookings/index.blade.php
  - resources/views/modules/invoices/index.blade.php
  - resources/views/modules/payments/index.blade.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Index UI Cleanup:
  - restored compact index layout standard
  - removed duplicate headers
  - merged status tabs into filter area
  - preserved AJAX/filter query behavior
  - reduced unnecessary cards
  - improved table readability
  - preserved i18n and currency rules
- Guardrails maintained:
  - no database changes
  - no route changes
  - no business logic changes

## 2026-05-21 (Modern Index UI Standardization)
- Scope: modern compact index layout standardization with preserved existing filter behavior.
- Updated files:
  - resources/views/modules/vendors/index.blade.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/bookings/index.blade.php
  - resources/views/modules/invoices/index.blade.php
  - resources/views/modules/payments/index.blade.php
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Modern Index UI Standardization:
  - compact filter toolbar
  - merged quick tabs into filter area
  - modern table rows/status/action
  - preserved existing filter behavior
  - preserved i18n/currency rules
  - reduced unnecessary cards and duplicate headers

## 2026-05-21 (Itineraries Index Action Dropdown Standardization)
- Scope: standardize compact table action UX on itineraries index using reusable dropdown component.
- Updated files:
  - resources/views/components/ui/table-action-dropdown.blade.php
  - resources/views/modules/itineraries/index.blade.php
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Changes:
  - added reusable `x-ui.table-action-dropdown` component
  - dropdown closes on outside click and `Esc`
  - replaced inline `details/summary` action menu in itineraries index (desktop + mobile card) with reusable component
  - preserved permission guards and existing action routes/logic

## 2026-05-21 (Table Action Dropdown Rollout Standard)
- Scope: make dropdown action (`...`) the default standard for table action columns on refactored index pages.
- Updated files:
  - resources/views/modules/vendors/index.blade.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/bookings/index.blade.php
  - resources/views/modules/invoices/index.blade.php
  - resources/views/modules/payments/index.blade.php
  - docs/blueprint/VOYEX_UI_COMPONENT_GUIDE.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Standardized behavior:
  - table action uses `x-ui.table-action-dropdown`
  - `View/Detail` moved into dropdown for compact and consistent action column
  - permission guards and existing action routes remain unchanged
  - dropdown item visuals standardized with icon + spacing and divider before destructive actions

## 2026-05-22 (Inquiry Priority Multilanguage Update)
- Scope: ensure inquiry priority options are fully translatable via ui_phrase() for all active locales.
- Updated files:
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Changes:
  - added explicit i18n keys for priority values: low, 
ormal, high
  - values now render correctly when priority filter/options use raw value keys
  - supports existing AJAX filter flow without page reload
## 2026-05-22 (Mandatory UI Compatibility Governance)
- Scope: enforce mandatory quality gate for every UI update.
- Updated files:
  - docs/blueprint/VOYEX_I18N_CURRENCY_UI_STANDARD.md
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/I18N_GUIDE.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Governance rules (mandatory each change):
  - every updated page must pass multi-language audit.
  - if page displays money values, it must pass multi-currency audit using existing formatter/components.
  - every updated UI must pass light/dark mode compatibility audit (readability, contrast, interactive states).
  - changes are considered incomplete until all three audits pass.

## 2026-05-22 (Inquiries Index Status Standardization)
- Scope: align inquiry index status presentation with the enforced UI governance standard.
- Updated files:
  - resources/views/modules/inquiries/index.blade.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Changes:
  - standardized status rendering to x-ui.status-badge (desktop + mobile)
  - added dedicated Status field in inquiry index table/card info for consistency
  - fixed itinerary availability label key to shared i18n phrase Available (:count)
  - fixed desktop empty-state table colspan to match current column count
- Quality gate:
  - multi-language: pass (status/labels use translatable keys)
  - multi-currency: N/A (no currency displayed on this page)
  - light/dark: pass (status badge + text classes remain dual-mode safe)

## 2026-05-22 (Inquiries Index Full Compliance Audit)
- Scope: enforce mandatory quality gate on inquiries.index for i18n, status standardization, and theme compatibility.
- Updated files:
  - resources/views/modules/inquiries/index.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Changes:
  - standardized inquiry status presentation to x-ui.status-badge on desktop and mobile
  - added dedicated Status field in index table/card to match UI standard
  - aligned itinerary availability phrase to shared key Available (:count)
  - completed missing i18n keys used by inquiries index (subtitle, empty-state guidance, and inquiry status value labels)
  - completed locale phrases for zh_Hant/zh_Hans used directly in this page (Reset, No Itinerary Yet, :size/page, Priority, Deadline, etc.)
- Quality gate result:
  - multi-language: pass
  - multi-currency: N/A (no monetary values shown on this page)
  - light/dark mode: pass

## 2026-05-22 (Itineraries Index Filter Standardization)
- Scope: align itineraries index filter with main baseline (Customers/Agents) and enforce mandatory quality gate.
- Updated files:
  - resources/views/modules/itineraries/index.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Changes:
  - removed filter title/description block and kept a single compact filter card
  - standardized filter layout to compact grid and kept required filters only (	itle, destination, duration, per_page)
  - standardized reset button to secondary style with aligned control height/radius
  - converted remaining hardcoded user-facing text in itinerary index to i18n (Highlighted, log line itinerary label)
  - synced new i18n key Highlighted across active locales
- Quality gate result:
  - multi-language: pass
  - multi-currency: N/A (no monetary values shown on this page)
  - light/dark mode: pass

## 2026-05-22 (Quotations Index Filter Standardization)
- Scope: align quotations index filter with the enforced baseline filter standard.
- Updated files:
  - resources/views/modules/quotations/index.blade.php
  - app/Http/Controllers/Sales/QuotationController.php
  - docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md
  - docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md
- Changes:
  - replaced x-ui.filter-bar filter block with single compact filter card layout
  - removed duplicate quick filter controls and removed explicit Apply button
  - standardized reset action to secondary button with aligned height/radius
  - preserved required filters only (q, status, per_page) and AJAX behavior
  - added backend min-3 keyword guard for quotation search to align with filter standard
- Quality gate result:
  - multi-language: pass (all visible text uses i18n helpers)
  - multi-currency: pass (quotation amount remains rendered via x-ui.money)
  - light/dark mode: pass
- Adjustment:
  - quick section tabs (Upcoming/Passed/Converted) are preserved and moved outside the filter card, so navigation behavior remains unchanged while filter layout stays standardized.
- Adjustment:
  - moved section tabs (Upcoming Quotations, Passed Quotation, Converted Quotations) into the same desktop table card and placed above the table header.
  - removed Quick Status: label text.

## 2026-05-22 (Global CRUD Notification De-duplication)
- Scope: enforce single-source flash notification rendering across module CRUD pages to prevent duplicate success/error alerts.
- Updated files:
  - resources/views/layouts/master.blade.php (reference standard: global flash include remains single render point)
  - resources/views/modules/airports/index.blade.php
  - resources/views/modules/activities/partials/_index-results.blade.php
  - resources/views/modules/bookings/index.blade.php
  - resources/views/modules/company-settings/edit.blade.php
  - resources/views/modules/currencies/index.blade.php
  - resources/views/modules/customers/import.blade.php
  - resources/views/modules/customers/index.blade.php
  - resources/views/modules/destinations/index.blade.php
  - resources/views/modules/food-beverages/index.blade.php
  - resources/views/modules/hotels/partials/_editor.blade.php
  - resources/views/modules/hotels/partials/_index-results.blade.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/inquiries/show.blade.php
  - resources/views/modules/island-transfers/index.blade.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/quotations/validate.blade.php
  - resources/views/modules/roles/partials/_index-results.blade.php
  - resources/views/modules/services/index.blade.php
  - resources/views/modules/tourist-attractions/partials/_index-results.blade.php
  - resources/views/modules/transports/index.blade.php
  - resources/views/modules/users/index.blade.php
  - resources/views/modules/vendors/index.blade.php
- Changes:
  - removed per-page and per-partial inline session('success')/session('error') rendering in modules
  - removed local hotels editor flash session block to avoid duplicate success message path
  - retained single global flash renderer via components.flash-messages in master layout
- Result:
  - CRUD notifications now render once per request lifecycle (single global source)
  - prevents duplicate alert output on pages that previously had local and shared flash rendering paths

## 2026-05-22 (AJAX Notification Consistency - Single Global Channel)
- Scope: continue global de-duplication by standardizing AJAX CRUD notifications to one global channel across modules.
- Updated files:
  - resources/js/app.js
  - resources/views/modules/tourist-attractions/index.blade.php
- Changes:
  - introduced global window.AppFlash.show(messages, type, timeout) helper in app.js
  - AppFlash renders toast-like notifications with unified success/warning/error style and light/dark support
  - migrated Hotels AJAX editor notifications (success/warning/validation error) to AppFlash
  - migrated Tourist Attractions AJAX status/delete notifications to AppFlash
  - removed remaining module-specific inline AJAX notice rendering to prevent duplicate or inconsistent notification behavior
- Result:
  - redirect-based CRUD uses one server flash renderer (components.flash-messages)
  - AJAX-based CRUD uses one client flash renderer (AppFlash)
  - each action now produces one visible notification path only (no double render).

## 2026-05-22 (Quotation -> Create Booking Auto-Generate Items)
- Scope: optimize Create Booking flow from quotation detail page so booking draft is fully prepared without extra clicks.
- Updated files:
  - resources/views/modules/bookings/_form.blade.php
- Changes:
  - when Create Booking is opened with quotation_id, booking form now auto-loads quotation travel date, order number, customer/pax meta, and auto-generates booking items
  - when quotation selection changes in booking form, item rows are regenerated automatically to match selected quotation
  - retained manual Generate button as fallback, while default flow becomes zero-click generation from quotation
- Result:
  - click Create Booking on quotation detail now leads directly to prefilled booking create page with quotation items already present.

## 2026-05-22 (Inquiry Detail - Direct Create Itinerary Action)
- Scope: add direct action on Inquiry detail page to speed up inquiry-to-itinerary flow.
- Updated files:
  - resources/views/modules/inquiries/show.blade.php
- Changes:
  - added Create Itinerary button in inquiry detail page actions
  - button points to oute('itineraries.create', ['inquiry_id' => ->id]) so itinerary create page opens with inquiry context prefilled
- Result:
  - from Inquiry detail, user can create itinerary in one click without returning to index page.

## 2026-05-22 (Inquiry Detail Sidebar - Related Records Standardization)
- Scope: refine inquiry detail sidebar related-data card per updated UX requirement.
- Updated files:
  - app/Http/Controllers/Sales/InquiryController.php
  - resources/views/modules/inquiries/show.blade.php
- Changes:
  - removed sidebar heading Related Records`r
  - added Related Itineraries list section (shown only when connected itineraries exist)
  - kept Related Quotations list section (shown only when connected quotations exist)
  - related-data card is now rendered only when at least one connected itinerary/quotation exists
  - removed empty-state text for missing related records from this card

## 2026-05-22 (Inquiry H-1 Deadline Reminder Notification - Superseded 2026-06-08)
- Scope: historical H-1-only reminder rule; superseded by the 2026-06-08 priority-window reminder rule.
- Updated files:
  - app/Http/View/SidebarComposer.php
  - app/Http/Controllers/Sales/InquiryController.php
  - routes/web.php
  - resources/views/layouts/master.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
- Behavior:
  - historical behavior showed reminders only when user had assigned/owned inquiries with deadline at H-1 and still no quotation
  - reminder is hidden when count is zero
  - polling endpoint keeps count updated and can trigger browser notification for newly detected H-1 reminders
  - notification query excludes inquiries that already have quotation (direct or via linked itinerary quotation)

## 2026-05-22 (Inquiry Single-Handler & Relationship Lock Rules)
- Scope: enforce strict ownership and relationship constraints across Inquiry, Itinerary, Quotation, and Booking flows.
- Updated files:
  - app/Policies/InquiryPolicy.php
  - app/Http/Controllers/Admin/ItineraryController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Http/Controllers/BookingController.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
- Rules enforced:
  - inquiry handling restricted to role Reservation/Manager/Director
  - inquiry handler ownership locked to one user via ssigned_to (fallback created_by for legacy rows)
  - itinerary create/edit inquiry selector now excludes inquiries already handled by other users and excludes inquiries that already have quotation
  - quotation create flow enforces inquiry handler claim/ownership + single quotation chain per inquiry
  - quotation edit/update no longer allows switching to another inquiry (locks to existing inquiry)
  - booking create flow filters eligible quotations to current inquiry handler ownership and enforces one quotation -> one booking
  - mutation guards for itinerary/quotation/booking now include linked inquiry handler ownership check
- Outcome:
  - one inquiry can only be processed by one handler user
  - one inquiry maps to one quotation chain (revision-based updates)
  - one quotation maps to one booking
  - non-handler users are limited to view-only behavior on linked records.

## 2026-05-22 (Handled By Ownership Standardization Across Inquiry Flow)
- Scope: complete handled-by rollout so inquiry ownership is explicit, assignable, and consistently enforced across related modules.
- Updated files:
  - database/migrations/2026_05_22_120000_add_handled_by_to_inquiries_table.php
  - app/Models/Inquiry.php
  - app/Http/Controllers/Sales/InquiryController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - app/Http/Controllers/BookingController.php
  - app/Http/Controllers/Admin/ItineraryController.php
  - app/Http/View/SidebarComposer.php
  - app/Policies/InquiryPolicy.php
  - resources/views/modules/inquiries/_form.blade.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/inquiries/show.blade.php
  - resources/views/modules/quotations/index.blade.php
  - resources/views/modules/bookings/index.blade.php
  - resources/views/modules/invoices/index.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
- Changes:
  - added `handled_by` column (FK -> users) on inquiries and backfilled from legacy `assigned_to`
  - inquiry create/edit now supports explicit handler assignment to Reservation/Manager/Director only
  - ownership checks and auto-claim flows now prioritize `handled_by` (fallback `assigned_to`/`created_by` for legacy)
  - inquiry reminders (H-1) and related eligibility queries now read `handled_by` first
  - added `Handled By` visibility on inquiry/quotation/booking/invoice index surfaces and inquiry detail
  - added i18n keys for new handled-by labels and validation messages
- Outcome:
  - inquiry can be created by all users, but handling ownership is explicitly attached to eligible roles and stays consistent across downstream modules.

## 2026-05-22 (Ownership Hardening v2: Handler-Lock for Quotation/Booking/Invoice Mutations)
- Scope: enforce final ownership lock so linked commercial documents can only be mutated by the inquiry handler.
- Updated files:
  - app/Policies/QuotationPolicy.php
  - app/Policies/BookingPolicy.php
  - app/Http/Controllers/Finance/InvoiceController.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
- Changes:
  - quotation and booking policies now resolve ownership from inquiry handler chain (`handled_by` -> `assigned_to` -> `created_by` fallback)
  - invoice edit/update/issue/void/cancel now blocked for non-handler users via inquiry-chain ownership guard
  - added i18n phrase for invoice ownership denial
- Outcome:
  - mutation rights are now aligned with one-handler ownership across inquiry, quotation, booking, and invoice.

## 2026-05-22 (Rule Update: One Inquiry Can Have Multiple Quotations - Superseded 2026-06-08)
- Scope: historical quotation relation rule; superseded by the 2026-06-08 one-to-one inquiry quotation rule.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
- Changes:
  - removed create-time guard that blocked additional quotations for the same inquiry
  - removed inquiry selector restriction that only allowed inquiries without quotations
  - kept existing relation model (`quotations.inquiry_id`) so each quotation still links to one inquiry only
- Outcome:
  - this historical rule is no longer active as of 2026-06-08.
  - each quotation remains attached to exactly one inquiry.

## 2026-05-22 (Rule Update: One Inquiry Can Link to Multiple Itineraries)
- Scope: align itinerary selection flow with updated relation rule.
- Updated files:
  - app/Http/Controllers/Admin/ItineraryController.php
- Changes:
  - removed inquiry selector restriction that blocked inquiries already having quotation
  - inquiry-itinerary relation remains through `inquiry_itinerary_references` pivot
- Outcome:
  - one inquiry can now be linked to multiple itineraries from create/edit itinerary flow.

## 2026-05-22 (Itinerary -> Quotation Guard: Must Select Unhandled Inquiry)
- Scope: enforce inquiry selection rule when generating quotation from itinerary.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
- Changes:
  - quotation create inquiry dropdown now uses unhandled inquiries only
  - if selected itinerary has no linked inquiry reference, backend now requires user to choose an unhandled inquiry first
  - added validation guard to reject handled inquiry selection on this flow
- Outcome:
  - quotation generation from itinerary now follows explicit ownership assignment flow via unhandled inquiry selection.

## 2026-05-22 (Inquiry Eligibility Rule for Itinerary-Based Quotation Generation)
- Scope: enforce inquiry selection policy for quotation generation from itinerary.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/quotations/_form.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
- Rules enforced:
  - inquiry is required when creating quotation
  - selected inquiry must be non-final
  - selected inquiry must be either handled by current user or still unhandled
  - inquiry list on quotation create follows the same eligibility policy
- Outcome:
  - itinerary can be used by all users for quotation generation, but inquiry assignment remains controlled and ownership-safe.

## 2026-05-22 (Inquiry Assignment Refactor: Assigned To as Single Input Source)
- Scope: remove user-facing handled-by input ambiguity and standardize inquiry assignment flow.
- Updated files:
  - app/Http/Controllers/Sales/InquiryController.php
  - resources/views/modules/inquiries/_form.blade.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/inquiries/show.blade.php
  - lang/en/ui_core.php
  - lang/zh_Hant/ui_core.php
  - lang/zh_Hans/ui_core.php
- Changes:
  - inquiry form now uses `assigned_to` input only (optional)
  - `handled_by` is now auto-synced from `assigned_to` on create/update
  - edit guard remains: when inquiry already has handler, assignment cannot be changed
  - inquiry index/show labels aligned to `Assigned To`
  - added i18n keys for new assignment messages
- Outcome:
  - assignment flow is consistent: user sets `Assigned To`; if set, selected user becomes inquiry handler automatically.

## 2026-05-22 (Inquiry Detail: Generate Quotation Action + Inquiry Prefill)
- Scope: add direct quotation generation action from inquiry detail page.
- Updated files:
  - resources/views/modules/inquiries/show.blade.php
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/quotations/_form.blade.php
- Changes:
  - added `Generate Quotation` button on inquiry detail header
  - button routes to quotation create with `inquiry_id` prefill
  - quotation create now accepts and validates prefilled inquiry in selectable inquiry list
  - inquiry field defaults to prefilled inquiry value in quotation form
- Rule:
  - itinerary remains mandatory in quotation create flow (already enforced by required validation)
- Outcome:
  - from inquiry detail, user can jump directly to quotation create while still being required to choose itinerary before saving.

## 2026-05-22 (Inquiry Module Refactor & Optimization)
- Scope: clean up inquiry module implementation, remove unused paths, and standardize assignment ownership flow.
- Updated files:
  - app/Http/Controllers/Sales/InquiryController.php
  - app/Http/Controllers/Sales/QuotationController.php
  - resources/views/modules/inquiries/_form.blade.php
  - resources/views/modules/inquiries/index.blade.php
  - resources/views/modules/inquiries/show.blade.php
  - docs/blueprint/VOYEX_STATUS_MATRIX.md
- Refactor highlights:
  - removed unused `SOURCE_LABELS` usage and simplified source option flow (`SOURCE_OPTIONS` only)
  - removed unused data payloads from inquiry index/show controller responses
  - unified action visibility checks in inquiry views (`canProcessInquiry`) to remove duplicated logic
  - removed dead/unused inquiry-related methods in quotation controller and eliminated duplicate resolver branch
- Rule documentation:
  - documented finalized inquiry ownership and processing action rules in status matrix blueprint.

## 2026-05-22 (Auto Link Itinerary to Inquiry from Quotation Flow)
- Scope: ensure itinerary used in quotation generation is visible under inquiry references.
- Updated files:
  - app/Http/Controllers/Sales/QuotationController.php
- Changes:
  - added automatic upsert to `inquiry_itinerary_references` when quotation is created/updated/revised
  - link uses `(inquiry_id, itinerary_id)` pair from quotation
- Outcome:
  - itinerary selected as quotation reference now appears in inquiry index/detail related itinerary data.

## 2026-06-15 (Controller-First Index Rendering Refactor)
- Scope: reduce Blade-side processing on heavy index pages and stabilize list rendering by preparing final display data in controllers.
- Updated files:
  - `app/Http/Controllers/Sales/InquiryController.php`
  - `resources/views/modules/inquiries/index.blade.php`
  - `app/Http/Controllers/BookingController.php`
  - `resources/views/modules/bookings/index.blade.php`
  - `app/Http/Controllers/Finance/PaymentController.php`
  - `resources/views/modules/payments/index.blade.php`
  - `app/Http/Controllers/Admin/FoodBeverageController.php`
  - `resources/views/modules/food-beverages/index.blade.php`
- Refactor highlights:
  - inquiry index tabs, row action flags, quotation summary state, and compact display fields now come from controller-prepared arrays
  - booking index KPI cards, datalist suggestions, row action permissions, and summary fields now come from controller-prepared arrays
  - payment quick-status tabs and row display payloads now come from controller-prepared arrays
  - food & beverage index meal-session badges, data-attention flags, pricing display strings, and activation toggle metadata now come from controller-prepared arrays
- Outcome:
  - index Blade files are now significantly thinner and focused on rendering final data only
  - repeated desktop/mobile computations are removed from views, reducing template fragility and improving maintainability
  - `php artisan view:cache` passes after the refactor, confirming Blade compilation stability

## 2026-06-15 (Controller-First Index Rendering Refactor, Batch 2)
- Scope: continue removing Blade-side index processing for quotation and island transfer modules.
- Updated files:
  - `app/Http/Controllers/Sales/QuotationController.php`
  - `resources/views/modules/quotations/index.blade.php`
  - `app/Http/Controllers/Admin/IslandTransferController.php`
  - `resources/views/modules/island-transfers/index.blade.php`
- Refactor highlights:
  - quotation index now prepares active section metadata, section tabs, filtered status options, metric values, row display payloads, and action visibility in controller
  - island transfer index now prepares row thumbnails, transfer labels, vendor location strings, duration/distance labels, and toggle action metadata in controller
  - Blade index pages now consume controller-prepared `*Rows`, `*Tabs`, `*Metrics`, and `perPageOptions` values with minimal inline logic
- Outcome:
  - quotation and island transfer index pages now follow the same controller-first rendering pattern used in the previous batch
  - Blade compile remains stable after the second batch (`php artisan view:cache` passed)

## 2026-06-15 (Controller-First Index Rendering Refactor, Batch 3)
- Scope: move itinerary index aggregation logic out of Blade without changing existing list behavior.
- Updated files:
  - `app/Http/Controllers/Admin/ItineraryController.php`
  - `resources/views/modules/itineraries/index.blade.php`
- Refactor highlights:
  - itinerary index now prepares `itineraryRows` in controller with:
    - title text including start/end highlight
    - creator display label
    - quotation popover links
    - transport-capacity summary
    - grouped item-list popover data
    - highlighted item marker key
    - generate-quotation visibility
    - action URLs and permission flags
  - desktop and mobile list layouts now render the same controller-prepared row payload instead of rebuilding item collections independently in Blade
- Outcome:
  - the heaviest duplicated index-page computation in the project has been centralized in controller helpers
  - itinerary list behavior remains intact while the Blade view becomes substantially thinner and safer to maintain

## 2026-06-15 (Controller-First Index Rendering Refactor, Batch 4)
- Scope: complete the next low-risk cleanup batch for system module index and customer index.
- Updated files:
  - `app/Http/Controllers/Admin/ServiceController.php`
  - `resources/views/modules/services/index.blade.php`
  - `app/Http/Controllers/Sales/CustomerController.php`
  - `resources/views/modules/customers/index.blade.php`
- Refactor highlights:
  - service manager index now prepares module card display state, snapshot counts, and toggle metadata in controller
  - service toggle action is now guarded server-side with the same activation-management policy used by other modules
  - customer index now prepares finalized `customerRows`, per-page options, and reusable sidebar summary data in controller
  - customer index now renders the reusable `module-index-sidebar-info` component with `Customer/Agent Info` summary content from controller-prepared filtered data
  - removed unused customer index query/loading paths that were not rendered by the page
- Outcome:
  - both index pages are thinner and more consistent with the controller-first rendering standard
  - customer sidebar behavior now matches the project guidance in `AGENTS.md`
  - Blade compilation remains stable after the batch

## 2026-06-15 (Controller-First Index Rendering Refactor, Batch 5)
- Scope: continue standardizing admin master-data index pages with similar activation/toggle behavior.
- Updated files:
  - `app/Http/Controllers/Admin/AirportController.php`
  - `resources/views/modules/airports/index.blade.php`
  - `app/Http/Controllers/Admin/DestinationController.php`
  - `resources/views/modules/destinations/index.blade.php`
  - `app/Http/Controllers/Admin/TransportController.php`
  - `resources/views/modules/transports/index.blade.php`
- Refactor highlights:
  - airport index now prepares `airportRows`, toggle metadata, location summary, destination label, and per-page options in controller
  - destination index now prepares `destinationRows`, linked-data summaries, location labels, and toggle metadata in controller
  - transport index now prepares `transportRows`, formatted type/unit/rate display values, toggle metadata, and per-page options in controller
  - mobile card rendering for destination index was moved out of the desktop-only wrapper so responsive rendering stays correct
- Outcome:
  - three more index pages now follow the same controller-first rendering contract used by the previous batches
  - desktop/mobile templates no longer duplicate activation/status formatting logic
  - Blade compile remains stable after the batch

## 2026-06-15 (Itinerary Index Blade Stabilization)
- Scope: eliminate fragile nested Blade structures on itinerary index after a runtime syntax error was reported.
- Updated files:
  - `resources/views/modules/itineraries/index.blade.php`
  - `resources/views/modules/itineraries/partials/_index-item-popover.blade.php`
  - `resources/views/modules/itineraries/partials/_index-action-dropdown.blade.php`
- Refactor highlights:
  - moved the deeply nested item-list popover markup into a dedicated partial reused by desktop and mobile layouts
  - moved the itinerary action dropdown markup into a dedicated partial reused by desktop and mobile layouts
  - reduced repeated nested `@foreach` / `@if` sections in the main index Blade so directive balancing is easier to maintain
  - aligned mobile duration text to the controller-prepared `duration_label`
- Outcome:
  - itinerary index Blade is now substantially less error-prone
  - the reported `unexpected token "endforeach"` issue is cleared after `view:clear` + `view:cache`
  - shared markup reuse also reduces future drift between desktop and mobile rendering

## 2026-06-15 (Itinerary Detail: Island Transfer Vendor Display)
- Scope: improve `Schedule by Day` metadata rendering for island transfer items on itinerary detail.
- Updated files:
  - `resources/views/modules/itineraries/show.blade.php`
- Changes:
  - island transfer schedule items now carry `vendor_name` in the per-day display payload
  - finalized island transfer display format in `Schedule by Day` to:
    - type label: `ISLAND TRANSFER`
    - title row: service name only
    - metadata row: `city/region | start time - end time`
- Outcome:
  - itinerary detail now shows a cleaner, simpler island transfer card format without changing item ordering or interaction flow
