# Roadmap Changelog Archive

Last Updated: 2026-04-28


Historical roadmap changelog entries were moved here during documentation consolidation on 2026-04-09.

Use this file for detailed historical records.

---

## Entry Index

1. 2026-04-27
2. 2026-04-28
3. 2026-04-06
4. 2026-04-01
5. 2026-03-30
6. 2026-03-23
7. 2026-03-13
8. 2026-03-17 (Entry 1)
9. 2026-03-16
10. 2026-03-17 (Entry 2)

## Entries

## 2026-04-28

- Fixed Itinerary PDF runtime error `Call to a member function getKey() on array` by normalizing merged day-item datasets into valid collections before rendering.
- Aligned quotation monetary outputs so `Amount`/`Final Amount` are consistent across:
  - quotation index,
  - quotation detail,
  - quotation PDF.
- Implemented two-way `order_number` synchronization between Itinerary and Quotation:
  - quotation generated from itinerary inherits itinerary order number,
  - if missing, order number can be entered in quotation create/edit,
  - updates from either side keep both records synchronized.
- Simplified quotation create/edit `Inquiry Detail` presentation into a single card with compact 2-column grid list format.
- Fixed Day Planner behavior so `+ Transport` works for Day 2 and subsequent days in itinerary create/edit.
- Updated itinerary F&B item display format to `F&B Name, Region, Vendor`.
- Updated day-completion rule so a day can be `Complete` when only start and end points are present.
- Renamed review labels and preserved HTML formatting:
  - `Include & Exclude` -> `Inclusions & Exclusions`,
  - inclusion/exclusion content now follows stored HTML format output.
- Implemented service-item completeness visual highlight policy and final scope:
  - `Vendor`: highlight when map URL/destination is missing,
  - `Activities`: highlight when gallery/destination/activity type is missing,
  - `F&B`: highlight when gallery/destination/service name/activity or service type is missing,
  - `Tourist Attraction`: highlight when gallery/map URL is missing,
  - `Island Transfer`: highlight disabled per final decision,
  - `Hotel` and `Transport`: no highlight changes.
- Resolved Blade parse regression `syntax error, unexpected token "endforeach"` in service item index pages by replacing short `@php(...)` directives with safe block `@php ... @endphp` and clearing compiled view cache.

## 2026-04-27

- Added **Editor Manual Item Validation Queue** for manual items created from Itinerary Day Planner (`Attraction`, `Activity`, `F&B`).
- New routes:
  - `GET itineraries/manual-item-notifications/poll`
  - `GET itineraries/manual-item-validation-queue`
  - `PATCH itineraries/manual-item-validation-queue/{activityLog}/validate`
- Added dedicated queue page:
  - `resources/views/editor/manual-item-queue.blade.php`
  - includes `Open Item` and `Mark as Validated` actions.
- Validation state is persisted in `activity_logs.properties`:
  - `validated_at`, `validated_by`, `validated_by_name`, `requires_validation`.
- Notification bell in topbar is now wired to Editor queue and shows pending count only (not yet validated).
- Sidebar now includes `Manual Item Queue` menu entry for editor workflow.
- Role mapping fix:
  - default `Editor` permission set now includes `module.itineraries.access` (required by queue + notification endpoints).
- Added technical documentation:
  - `docs/technical/EDITOR_MANUAL_ITEM_VALIDATION_QUEUE.md`

## 2026-04-06

- Fixed Activity Log (Audit Trail) on Activity, TouristAttraction, FoodBeverage, and Itinerary models:
  - Root cause: models were using `HasAudit` trait (for `created_by`/`updated_by` only) instead of `LogsActivity` trait (for activity ledger logging).
  - Solution: added `LogsActivity` trait to all 4 models (`Activity.php`, `TouristAttraction.php`, `FoodBeverage.php`, `Itinerary.php`).
  - Impact: each model now auto-logs created/updated/deleted events to `activity_logs` table with full change tracking.
  - UI fix: Activity Timeline in Itinerary detail page now shows all changes to Activity/Attraction/F&B/Itinerary records.
  - no breaking changes; all existing `created_by`/`updated_by` functionality preserved; codebase gained activity audit trail.
  - added documentation file: `ACTIVITY_LOG_FIX.md` with detailed analysis, implementation guide, and verification steps.
- Login branding now uses dynamic company name from database (`company_settings.company_name`) instead of hardcoded `VOYEX CRM`.
- Added new view composer `App\Http\View\CompanyBrandComposer`:
  - safely reads `company_name` with fallback to `config('app.name')` when table/data is unavailable.
  - prevents login page failure on fresh setup before migration.
- Registered composer for `auth.login` in `AppServiceProvider`.
- Updated login view (`resources/views/auth/login.blade.php`):
  - page title now uses dynamic company name,
  - brand heading now uses dynamic company name,
  - footer brand text now uses dynamic company name + current year.
- Extended login branding configurability:
  - added new DB column `company_settings.tagline` via migration `2026_04_06_120000_add_tagline_to_company_settings_table.php`,
  - login tagline now uses `company_settings.tagline` (fallback: `Smart Travel CRM Platform`),
  - login footer suffix now uses `company_settings.footer_note` (fallback: `All rights reserved.`).
- Updated Company Settings flow for new tagline field:
  - `CompanySetting` model fillable includes `tagline`,
  - `CompanySettingController` create-default + validation now supports `tagline`,
  - Company Settings UI now has dedicated `Tagline` input (separate from `Footer Note`).
- Hardened login brand composer for phased migration:
  - checks `Schema::hasColumn('company_settings', 'tagline')` before selecting tagline to avoid query break before migration execution.
- Fixed login footer/tagline entity rendering issue:
  - added deep HTML entity decode normalization in `CompanyBrandComposer` to prevent visible `&amp;` / `&amp;amp;` text in login branding.
- Updated Company Settings UI helper text:
  - `Footer Note` now explicitly indicates usage for login footer note only.
- Itinerary Create layout refinement (`resources/views/modules/itineraries/_form.blade.php`):
  - compacted schedule item card height/padding to reduce vertical space usage,
  - moved `Start Time`, `End Time`, and `Highlight` controls to the top-right area of each item card,
  - positioned drag handle at top-left area of each item card,
  - moved item sequence badge to the row below (left side), followed by `Item Type`, `Region`, and item selector in one compact line on desktop,
  - changed remove action to icon-only `X` button (with accessible `aria-label`),
  - preserved existing JS hooks/classes (`item-*`, `drag-handle`, `remove-row`) so reindex/sort/calc flow remains compatible.
- Itinerary item time display adjustment:
  - `Start Time` and `End Time` on item card header are now rendered as text (`Start Time: ... | End Time: ...`) instead of visible time inputs.
  - underlying `.item-start` / `.item-end` fields are retained as hidden inputs to keep existing calculation + submit payload intact.
  - added `syncRowTimeText()` in itinerary form JS so displayed text always follows recalculated hidden time values.
- Itinerary schedule header control alignment update:
  - on create/edit day section, `Start Tour`, `End Tour`, and action buttons (`Add Attraction`, `Add Activity`, `Add F&B`) are now placed in one unified row container (desktop), with responsive wrap fallback for smaller screens.
- Itinerary schedule action simplification:
  - replaced 3 add buttons (`Add Attraction`, `Add Activity`, `Add F&B`) with single `Add Item` button on each day section.
  - `Add Item` now appends one new schedule row and keeps item type selection in-row (`Item Type` dropdown) as the single source of type choice.
  - JS enhancement: new rows default to the last active item type in the same day section (fallback `attraction`) for smoother repetitive input.
- Itinerary schedule header control positioning tweak:
  - adjusted layout so `Start Tour` + `End Tour` stay on the left side and `Add Item` stays on the far right side in the same row (desktop), with responsive wrap fallback on smaller screens.
- Itinerary item region auto-select enhancement:
  - region can now auto-fill based on selected item city (`Attraction` / `Activity` / `F&B`) when item is chosen first.
  - user can still set region manually; once manually chosen, auto-overwrite is prevented unless type is switched/reset.
  - behavior is implemented in create/edit form JS with manual-state guard (`dataset.regionManual`).
  - when region is changed manually, current item selection (`Attraction` / `Activity` / `F&B`) is cleared to enforce re-selection based on the new region filter context.
- Itinerary detail map sidebar sticky behavior:
  - updated detail page map card container to `xl:sticky xl:top-6` so map section stays fixed while scrolling down on desktop and naturally returns to original position when scrolling back up.
  - synced technical docs in `ITINERARY_DETAIL_MAP_ARCHITECTURE.md` to reflect this behavior.
- Itinerary detail F&B item presentation update:
  - F&B row display is now formatted as requested:
    1) `Nama Vendor - Nama F&B - jenis item | meal_type`
    2) `Region | Start time - End time`
    3) `menu_highlights`
  - added F&B-specific timeline payload fields (`vendor_name`, `region`, `menu_highlights`) and prioritized itinerary meal type (`meal_type`) in display.
- Itinerary F&B meal type automation:
  - added DB column `itinerary_food_beverages.meal_type` via migration `2026_04_06_130000_add_meal_type_to_itinerary_food_beverages_table.php`.
  - backend save/update now auto-assigns meal type from F&B `start_time`:
    - `< 11:00` => `Breakfast`
    - `11:00 - 15:59` => `Lunch`
    - `>= 16:00` => `Dinner`
  - itinerary detail (`show`) now displays meal using stored itinerary `meal_type` first, with fallback to master F&B `meal_period` for legacy rows.
- QA note:
  - passed `php -l app/Http/View/CompanyBrandComposer.php`,
  - passed `php -l app/Providers/AppServiceProvider.php`,
  - passed `php -l app/Http/Controllers/Director/CompanySettingController.php`,
  - passed `php artisan view:cache` after itinerary form layout update.

---

## 2026-04-01

- Implemented multi-role quotation approval gate (hard requirement):
  - quotation can be `approved` only after all three are fulfilled:
    1) Manager approval,
    2) Director approval,
    3) at least one Reservation approval from non-creator user.
- Added new persistence layer for per-user approval log:
  - migration `2026_04_01_100000_create_quotation_approvals_table.php`,
  - model `App\Models\QuotationApproval`,
  - relation `Quotation::approvals()`.
- Updated `QuotationController@approve` flow:
  - approval now records per role/user and no longer directly forces status approved by single actor.
  - status auto-syncs to `pending` until all required roles are complete.
  - once complete, status becomes `approved`, `approved_by` follows Director approver, and invoice generation trigger remains intact.
- Updated `reject` and `setPending` behavior:
  - clear approval logs to ensure re-approval starts from clean state.
- Updated Quotation Validation UI (`edit` and `show`):
  - added approval checklist cards (Manager/Director/Reservation non-creator),
  - added `Waiting for` helper text,
  - added approval log list (role, approver name, timestamp),
  - enabled approve action for Manager/Director/Reservation (with reject limited to Manager/Director).
- Added Reservation access to quotation approval permission map:
  - `module.quotations.access`,
  - `quotations.approve`.
- Added dedicated UAT guide:
  - new doc `QUOTATION_APPROVAL_UAT_MATRIX.md` with positive/negative scenario matrix and execution order.
- Refined approval flow sequencing and access:
  - fixed approval route middleware so Reservation users with quotation module access can hit approve endpoint (no longer blocked by strict `quotations.approve` middleware mismatch).
  - enforced sequential approval gate in backend:
    - Reservation (non-creator) must approve first,
    - then Manager can approve,
    - then Director can approve.
  - UI approval button visibility now follows the same sequence guard on edit/show pages.
- Updated validation UX for reject flow:
  - removed generic `Add note` section from normal validation path,
  - reject action now opens modal and requires reason note (mandatory on backend and frontend).
- Implemented device-local timezone rendering (phase-1 rollout):
  - added reusable Blade component `resources/views/components/local-time.blade.php` to render UTC timestamps as local-device time in browser.
  - added global frontend localizer in `layouts/master.blade.php` for all nodes with `data-local-time`.
  - migrated datetime rendering in:
    - quotation module pages (`index`, `edit`, `show`),
    - quotation comments + audit partials,
    - activity timeline component,
    - currency rate history edit page,
    - inquiry communication timeline on inquiry detail,
    - invoice detail `Paid At`,
    - itinerary inquiry preview (`created_at`) with ISO payload + client-side local formatting.
  - kept database source-of-truth timestamps in UTC; conversion happens at display layer only.
- QA note:
  - passed `php artisan test tests/Feature/Modules/QuotationsGlobalDiscountRoleTest.php tests/Feature/Modules/QuotationsSmokeTest.php` (11 passed),
  - passed `php -l` syntax checks for modified files.

---

## 2026-03-30

- Standardized Create/Edit/Detail grid display across modules to follow Itinerary baseline UX:
  - mobile/tablet: stacked layout,
  - desktop (xl): main 8 / side 4 split.
- Updated global grid behavior in resources/css/app.css so module-grid-8-4 and module-grid-8-4 resolve to 8/4 at xl breakpoint (matching Itinerary pattern).
- Migrated outlier pages to shared grid structure:
  - currencies (create/edit),
  - users (create/edit),
  - hotels (create/edit),
  - invoices (show),
  - company-settings (edit).
- Added supporting sidebar info cards on migrated pages to keep right-panel UX consistent.
- QA note: passed php artisan view:cache after layout migration.
- Quotation Create/Edit hardening:
  - fixed corrupted Blade structure in `resources/views/modules/quotations/edit.blade.php` (Inquiry sidebar markup) that could break UI rendering.
  - aligned Create/Edit page header/actions with shared system standard (`page_title`, `page_subtitle`, `btn-*` actions).
  - standardized generate button style in quotation form to shared button system (`btn-outline-sm`).
  - fixed Quotation create itinerary dropdown source query so available itineraries are listed reliably (active + non-final + no active quotation).
  - tightened Quotation create itinerary filter to only show itineraries with no quotation record at all (including soft-deleted quotations), enforcing 1 itinerary = 1 quotation rule.
  - aligned Quotation edit itinerary source query with the same availability rules while still allowing the current itinerary.
  - added create-form empty-state hint when no eligible itinerary is available.
- Itinerary create hardening:
  - forced `is_active = true` on `ItineraryController@store` so every newly created itinerary is always active regardless of request payload.
- Quotation create UI consistency:
  - set `Generate` button minimum height to `42px` to match standard `app-input` control height.
  - aligned quotation item row vertical baseline by adding top margin (`mt-1`) on compact money inputs (`Unit Price` and `Discount`) so label/input alignment matches other fields.
  - removed helper/preview text below quotation item money fields so `Unit Price` and `Discount` no longer show extra text under inputs.
  - added dedicated quotation item row alignment classes (`quotation-item-label`, `quotation-item-control`, `quotation-item-money-field`) so Description/Qty/Unit Price/Discount Type/Discount stay perfectly aligned per row.
  - added new item pricing fields on Quotation create/edit: `Contract Rate` (readonly), `Markup Type` (`Fixed/Percent`), and `Markup`.
  - `Unit Price` on item row is now auto-calculated from `Contract Rate + Markup` and kept readonly for consistency.
  - backend validation and total calculation now include `contract_rate`, `markup_type`, and `markup`, including max 100% guard for percent markup.
  - added migration `2026_03_31_120000_add_contract_rate_and_markup_to_quotation_items_table.php` and updated `QuotationItem` model casts/fillable.
- Transport module rollout (`markup_type`, `markup`) completed:
  - added transport DB migration `2026_03_31_130000_add_markup_fields_to_transports_table.php` with safe backfill from legacy `publish_rate - contract_rate`.
  - updated `Transport` model fillable/casts for `markup_type` and `markup`.
  - updated `TransportController` validation + guard (percent markup max 100) and server-side auto compute for `publish_rate`.
  - updated transport create/edit form to include `Markup Type` and `Markup`, and set `Publish Rate` to readonly auto-calculated value.
  - updated transport index/show views to display markup information.
  - fixed transport create/edit JS money parser so backend decimal-formatted values are not misread as x100 (publish rate auto-calc now accurate).
  - enforced integer money scale for transport pricing (`contract_rate`, `markup`, `publish_rate`) and rounded server-side compute output.
  - added migration `2026_03_31_140000_change_transport_rate_precision_to_zero_scale.php` to switch those columns to `DECIMAL(15,0)`.
- added item-level UX validation improvements (`required` fields + item error banner) on quotation form.
- Fixed critical quotation total-calculation bug in `QuotationController::computeTotals()`:
  - per-item discount type variable no longer overrides quotation-level discount type,
  - ensures `discount_amount` and `final_amount` are computed from the correct header discount setting.
- Fixed quotation edit/store behavior for `Additional Items` (manual rows):
  - added `manual` into allowed `itinerary_item_type` list so manual rows pass validation and persist correctly.
  - fixed `computeTotals()` unit-price source selection so manual rows use submitted `unit_price` instead of being forced from empty `contract_rate`.
  - impact: editing quotation with manual additional items now updates persisted data and totals correctly.
- Refined Quotation PDF item table layout:
  - removed card wrapper and `Items` title above the item list so the PDF item section is table-only.
  - removed `Discount Type` and `Discount` columns from item rows in PDF output.
  - updated empty-state colspan and column width proportions to match the simplified 4-column table.
- Quotation Global Discount role guard hardening:
  - on Create/Edit form, fields `Global Discount Type`, `Global Discount Value`, and `Global Discount Amount` are now rendered only for roles `Director` and `Manager`.
  - backend store/update now enforces the same rule:
    - non-Director/Manager cannot inject or modify global discount via request payload.
    - on update by non-Director/Manager, existing global discount values are preserved.
- Added focused feature test matrix for Quotation Global Discount role behavior:
  - file: `tests/Feature/Modules/QuotationsGlobalDiscountRoleTest.php`.
  - covered scenarios: Manager/Director field visibility, Marketing field hidden, Manager can update global discount, Marketing cannot override existing global discount.
  - test result: `php artisan test tests/Feature/Modules/QuotationsGlobalDiscountRoleTest.php` passed (4 tests).
- Added Global Discount shortcut in Quotation Validation panel (Edit page):
  - new route `PATCH quotations/{quotation}/global-discount` (`quotations.global-discount`).
  - Manager/Director can update global discount directly from validation sidebar without full pricing form edit.
  - backend guard enforces role and blocks update on `final` / `approved` quotation (approved must be set pending first).
  - final amount is recalculated immediately from current subtotal and submitted global discount.
- Updated Quotation edit access policy:
  - `QuotationPolicy::update` now allows `Manager` and `Director` (as well as `Super Admin`) to edit quotation even if they are not the creator.
  - creator-only edit rule remains for other roles (for example Marketing can only edit their own quotation).
  - updated deny message in `QuotationController` to match new access rule.
- Quotation post-submit UX adjustment:
  - after successful create and update, user is now redirected to quotation detail page (`quotations.show`) instead of index.
  - smoke/role tests updated to assert new redirect behavior.
- Quotation form simplification for global discount:
  - removed `Global Discount Type` and `Global Discount Value` inputs from main Create/Edit form (`_form`) to avoid duplicate entry points.
  - global discount entry point is now centralized in Validation sidebar (Edit page) for Manager/Director.
  - hardened `QuotationController@update` so when main form submits without discount fields, existing global discount is preserved (not reset).
  - added back read-only `Global Discount Amount (Auto)` in main form summary so users can clearly see why `Final Amount` differs from `Sub Total`.
  - main form now carries hidden `discount_type`/`discount_value` for consistent frontend total preview while edit authority remains enforced by backend role guard.
  - fixed cross-role inconsistency on main-form `Global Discount Amount (Auto)`:
    - JS now reads discount source only from main-form hidden fields (`#main-global-discount-type`, `#main-global-discount-value`) scoped to the main quotation form.
    - removed ambiguous global selector usage that could accidentally read sidebar validation fields for Manager/Director.
    - fixed fixed-discount preview conversion (IDR source -> active display currency) so Reservation/Manager/Director see the same amount for the same quotation data.
  - strengthened DB-driven initial values on Create/Edit summary:
    - main form `Item Discount (Auto)` and `Global Discount Amount (Auto)` now initialize from persisted quotation data (when editing), not hardcoded zero.
  - namespaced sidebar validation fields to avoid form collision:
    - changed to `global_discount_type` and `global_discount_value` on validation sidebar form + controller endpoint mapping.
    - prevents `old()`/request cross-contamination between main quotation form and sidebar validation form.
  - stabilized Create/Edit quotation form rendering after latest adjustments:
    - added server-rendered fallback item table header in `_form` so header labels remain visible even if JS regroup step fails.
    - hardened `updateOverallDiscountBadge()` to skip hidden global-discount field and avoid unintended badge mutation side effects.
    - impact: item header visibility and summary auto-calc behavior are more resilient for all roles.
  - fixed numeric parser issue on quotation form auto-calculation:
    - improved `parsePercent()` handling for DB decimal format (e.g. `10.00`) so it no longer gets misread as `1000`.
    - added global discount percent clamp (0..100) on frontend preview to prevent invalid over-discount display.
    - impact: `Global Discount Amount` and `Final Amount` auto-preview now stays consistent with stored discount values.
- Documentation/process governance update:
  - added mandatory 4-step execution protocol to `PROJECT_GUIDELINES.md` as fixed development standard:
    1) full code/context understanding before change,
    2) mature solution design (performance + UI/UX + safety),
    3) clean implementation without regression,
    4) end-to-end verification after implementation.
- Expanded role-matrix test coverage for new validation-panel endpoint:
  - Manager/Director can update global discount via `quotations.global-discount`.
  - Marketing cannot update global discount via that endpoint.
  - added access tests: Manager/Director can open edit page for quotation created by another user, while non-owner Marketing is redirected.
  - latest test result: `php artisan test tests/Feature/Modules/QuotationsGlobalDiscountRoleTest.php` passed (8 tests).
- QA note: passed `php -l app/Http/Controllers/Sales/QuotationController.php` and `php artisan view:cache`.
- Adjusted itinerary detail right-side map card layout to `h-fit lg:self-start` so card height follows map content and removes empty stretched space below the map.
- Performed responsive hardening for Itinerary views (`create`, `edit`, `_form`, `show`, `index`) to prevent mobile/tablet horizontal clipping.
- Removed width-forcing day header constraint (`min-w-[280px]`) in itinerary form and enabled safe text wrapping for endpoint meta.
- Refined `Start Tour / End Tour` control row in itinerary form to wrap safely on small screens (labels no-wrap, time inputs responsive width, action buttons wrap).
- Added `min-w-0` guards on itinerary grid/card/aside wrappers and module-level CSS safety rules to avoid overflow-driven content cut-off.
- Added mobile CSS fallback for itinerary day header stacking and full-width travel connector rendering.
- QA note: static responsive audit completed on Blade/CSS structure; no new fixed-width class remains in itinerary day header area.
- Rebuilt itinerary detail map (`show`) with robust Leaflet initialization guard and safe render cycle.
- Standardized detail map route rendering to road-following polyline via OSRM only (removed straight-line fallback).
- Added itinerary detail map day filter stabilization (`All Days` / `Day N`) with safe re-render behavior.
- Added duration labels (`Xm`) per route segment on itinerary detail map.
- Added click interaction on duration labels to highlight connected marker pairs for overlapping route clarity.
- Fixed dynamic Day clone behavior in itinerary create/edit so start-point travel connector remains visible on Day N+.
- Clarified create/edit field label: `Travel from Day N Start Point to first item (minutes)`.
- Added per-row `Region (City)` selector beside `Item Type` on itinerary create/edit schedule items to filter Attraction/Activity/F&B options faster for large datasets.
- Standardized itinerary schedule option labels on create/edit:
  - Attraction: `Attraction name`
  - Activity: `Activity name - Vendor name`
  - F&B: `F&B name - Vendor name`
- Added `End Time` text indicator in top-right of each `Day N End Point` card (text-only, synchronized with itinerary time calculation) so users do not need to scroll to top.
- Added frontend `required` enforcement on mandatory itinerary create/edit fields, including dynamic conditional-required logic for start/end point items and hotel room selectors.
- Added automatic red `*` required indicator on labels for required fields in itinerary create/edit, including dynamic required fields.
- Simplified UI for all `Travel to next item (minutes)` inputs on itinerary create/edit:
  - half-width layout,
  - label removed (moved to placeholder),
  - car icon moved inside input (left side),
  - adjusted left-affix spacing so icon does not overlap placeholder/text.
- Adjusted `Day N Start Point` and `Day N End Point` layout so `Airport/Hotel` type selector and item selector stay in one row.
- Finalized Start/End Point hotel layout: when `Hotel` is selected, `Type + Item + Room` are now rendered in a single row (not split to next line).
- Refined Start/End Point row layout using responsive flex grouping to ensure `Type + Item + Room` remains one-row on tablet/desktop and does not break unexpectedly.
- Updated itinerary create/edit sidebar behavior: `Inquiry Detail` card is hidden when no inquiry is selected, and shown only after inquiry is chosen.
- Changed itinerary create/update post-submit redirect to itinerary detail page (`itineraries.show`).
- Added dedicated technical documentation: `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`.
- Synced references in `PROJECT_KNOWLEDGE_BASE.md` and `ITINERARY_CREATE_EDIT_FLOW.md`.

---

## 2026-03-23

- Started Hotels module implementation (admin controller, routes, sidebar, permissions, and base CRUD views).
- Added Hotels form sections for rooms, images, extra beds, seasonal prices, packages, and promos.
- Added Hotels into Super Admin Module Control Center (Product & Reservation group).
- Added auto-registration for Hotels module/permissions via ModuleService.
- Linked Transports to Vendors/Providers (vendor_id) for consistent provider data.
- Standardized Google Map location block template for Hotels Create/Edit: Location on Map -> Map URL -> Latitude/Longitude -> Address -> City/Province -> Country -> Destination.
- Documented map-field standard (address, city, province, country, latitude, longitude, destination) as rollout baseline for all map-enabled modules.
- Continued map-standard rollout to Accommodations, Airports, Vendors, and Tourist Attractions forms using the same field order template.
- Vendors form adjusted so Address uses standard `input` control (auto fill from Google Maps URL) for consistency with the map template.
- Added reusable partial `components/map-standard-section.blade.php` and refactored map-enabled forms to consume a single template include.

---

## 2026-03-13

- Added per-role dashboards (Administrator, Manager, Marketing, Reservation, Finance, Director, Editor) with Super Admin style.
- Standardized module permissions to Access + CRUD and added UI enhancements (template role, counters, CRUD badge).
- Implemented CRUD enforcement middleware (`module.permission:{moduleKey}`).
- Added friendly 403 access denied page.
- Updated sidebar to permission-first access for Itineraries.
- Enforced Inquiry edit/delete to creator-only (others read-only) and updated UI actions.
- Enforced creator-only edit/delete for Itineraries, Quotations, and Bookings (Super Admin override) with UI action guards.
- Centralized creator-only rules in Laravel policies and updated UI guards to use `@can`.
- Fixed dashboard KPI revenue aggregation to sum `quotations.final_amount` via joins (avoid missing column on bookings).
- Fixed Manager dashboard assigned user relation to use `assignedUser`.
- Standardized button styling system with modern hover/active states across UI.
- Replaced primary/secondary action buttons across views with `btn-primary`/`btn-secondary` for consistent UI.
- Converted status special buttons (Approve/Reject/Delete) to `btn-primary`/`btn-secondary`.
- Added compact sizing for table action buttons and disabled-state styling for button system.
- Added `btn-ghost` variant for tertiary actions (View/Back/Reset) and applied across views.
- Standardized page header action spacing with responsive stacking in `page-actions`.
- Added `filter-actions` layout helper to keep filter/reset rows aligned and responsive.
- Wrapped all on-screen tables inside `app-card` for UI consistency (excluding PDFs).
- Adjusted Customers table wrapper to ensure the card is visually applied.
- Tweaked Customers index spacing to align table card with filter column.
- Added `btn-outline` variant and applied it for neutral import/export actions.

---

## 2026-03-17 (Entry 1)

- Enabled soft deletes for core modules (Vendors, Activities, F&B, Accommodations, Airports, Transports, Destinations, Tourist Attractions, Inquiries, Itineraries, Quotations, Customers).
- Replaced Delete actions with Deactivate/Activate toggle buttons (Deactivate uses grey `btn-muted-sm`).
- Added Active/Inactive status badges in index tables and mobile cards.
- Updated controllers to use `withTrashed()` on index lists and to toggle soft delete state.
- Aligned `btn-muted-sm` sizing/height with other action buttons for consistent table actions.
- Converted table action buttons (Detail/View/Edit) to icon-only buttons to save space.
- Aligned button palette to the provided UI colors (#2364aa, #3da5d9, #73bfb8, #fec601, #ea7317).
- Added `btn-warning`/`btn-danger` variants and mapped to Set Pending / Reject / Delete actions.
- Updated default UI theme to match the provided reference (teal accent, soft gray background, rounded cards).
- Refined status badge styling to match the reference (pill, soft colors).
- Standardized table typography/spacing and enforced `app-table` across views.
- Updated Breeze button components to use the new button system (primary/secondary/danger).
- Added subtle zebra striping and consistent hover for `app-table` rows.
- Tightened action column spacing using `actions-compact` on index tables only.
- Normalized detail/show table headers to use shared `app-table` styling (removed custom thead backgrounds).
- Standardized show tables to `app-table w-full` for consistent spacing.
- Redesigned Customers index layout to 4/8 grid with themed filter card and updated table actions.
- Added Customers header statistics cards (country distribution + customer snapshot).
- Compacted Customers statistics cards spacing/typography for a tighter layout.
- Restyled Customers statistics cards to match Super Admin dashboard Action Center/Business Funnel look.
- Adjusted Customers statistics grid to 8/4 columns for better balance.
- Normalized Customers index view whitespace for cleaner templates.
- Removed empty spacer lines in Customers index template (no blank gaps between closing tags).
- Standardized all module index pages to Customers layout (12-col grid, filter card left, table/content right).
- Converted remaining index action text-links to `btn-*` for consistency.
- Normalized show/detail cards to use `app-card` for consistent spacing/typography.
- Standardized index action cells with flex + gap-2 for consistent button layout.
- Added `app-dl` typography for show page label/value consistency.
- Fixed broken Actions column markup after bulk alignment pass.
- Reduced button padding/size inside tables for more compact Actions.
- Fixed Customers index action buttons markup so Edit/Delete work correctly.
- Added hotels-related migrations (hotels, images, rooms, prices, types, promos, packages, facilities, extra beds) with corrected foreign keys and cleaned duplicate columns.
- Added Hotel domain models and Accommodation->Hotel linkage for future management.
- Removed ExtraBedOrder model and added RoomView model + migration, with HotelRoom linked to RoomView.
- Renamed hotel_prices migration to run after hotel_rooms (fix FK ordering).
- Renamed room_views migration to run before hotel_rooms (fix FK ordering).
- Added facility_traditional/facility_simplified columns to hotels to match import SQL schema.
- Added additional_info_traditional/additional_info_simplified columns to hotels to match import SQL schema.
- Added cancellation_policy_traditional/cancellation_policy_simplified columns to hotels to match import SQL schema.
- Added include/include_traditional/include_simplified columns to hotel_rooms to match import SQL schema.
- Inquiries: removed status/assigned_to inputs on create/edit; status defaults to draft and assigned_to uses current user (no assigned UI shown).
- Inquiries: updated filter grid so each input is at least half-card width (sm:grid-cols-2).
- Inquiries: added status summary cards below the header to standardize index statistics display.
- Inquiries: standardized status summary cards to use .app-card-grid (2 cols mobile, 6 cols desktop).
- Inquiries: fixed undefined $canManageInquiry on detail view by passing policy-based flag from controller.
- Customers: removed delete actions from UI and disabled destroy endpoint to prevent deletion.
- Customers: aligned statistics cards with app-card/app-card-grid standard.
- Customers: removed section headers so statistics show as points inside app-card.
- Customers: reworked stats layout to match Inquiries (summary cards above, filter/table grid below).
- Customers: replaced summary card letter badges with icons.
- Inquiries: replaced status summary letter badges with icons.
- Itineraries: inquiry detail notes now render sanitized HTML in preview.
- Itineraries: removed status input, default status to draft on create, and auto-process related inquiry after save.
- Airports: updated index UI to match Customers/Inquiries layout and card styling.
- Accommodations, Activities, Food & Beverages, Destinations: started aligning index UI to Customers/Inquiries standard (header/actions, filter grid, app-card table, mobile cards).
- Completed index UI alignment for Bookings, Invoices, Itineraries, Quotations, Roles, Services, Transports, Tourist Attractions, Users, Vendors, Currencies.
- Visual sweep: replaced remaining text links in index action areas with btn-outline-sm (Itineraries, Quotations).
- Added global index statistics composer and reusable component to standardize stats across all module index pages.
- Fixed itinerary detail view ternary class for fnb type (syntax error).
- Itinerary detail: fixed day control buttons (class hook + btn style toggle) for proper filtering and theme alignment.
- Itineraries index: added title/destination/duration filters and standardized per-page controls.

---

## 2026-03-16

- Itineraries index: destination filter now uses `destination_id` from Destinations module (strict match).
- Itineraries index: added safe fallback if `destination_id` column is not yet migrated (avoid SQL error).
- Global submit lock + spinner to prevent double submit on forms.
- Itineraries index: destination filter labels now show destination name only.
- Users index: added standard filters (search, role, per-page) with query persistence.
- Inquiry: follow-up reminder form now allowed for Reservation/Director/Manager roles (plus creator).
- Inquiry follow-up: store created_by and show creator name in reminder list.
- Inquiry follow-up: require reason via modal when marking reminder as done.
- Inquiry follow-up: moved reason modal outside table to keep table layout clean.
- Inquiry follow-up: render reason modal only for pending reminders.
- Inquiry follow-up: only inquiry creator can mark reminder done.
- Inquiry follow-up: mark done allowed for creator or assigned_to.
- Inquiry delete disabled (UI + controller).
- Inquiry reminder: done status shows view-reason icon with tooltip.
- Inquiry reminder: view-reason icon now opens modal with the reason.
- Inquiry reminder: reason modal now also shows reminder note.
- Inquiry reminder: note/reason now render stored HTML in modal.
- Added centralized activity logging (`activity_logs`) with polymorphic subjects + timeline component.
- Inquiry: activity timeline now includes reminder/communication events with user tracking.
- Inquiry detail: Activity Timeline moved below Inquiry Overview card.
- Activity timeline UI: simplified to short single-line format with timestamp + user.
- Activity timeline UI: removed per-item borders for compact list.
- Activity timeline UI: each item prefixed with "-".
- PDF Itinerary: removed "Travel from ..." line in day header.
- PDF Itinerary: each day starts on a new page (except day 1).
- PDF Itinerary: renamed column label to "Image".
- Itineraries index: show creator name under itinerary title.
- Itineraries index: destination shown under Duration.
- Itineraries index: removed Attractions column.
- Itineraries: removed Room Qty input for start/end point in create/edit.
- Itineraries: added color-coded cards for start/end points and item types.
- Itineraries: start/end point cards now change color based on type (airport/accommodation).
- Itineraries: base background for start/end point cards unified.
- Itineraries: Inquiry Detail now shows Reminder Note and Done Reason when available.
- Itineraries: Route Preview card is sticky on scroll (top-6).
- Itineraries: Destination + Duration fields aligned in a single row.
- Inquiry: Manager/Director can create and assign inquiry to Reservation users.
- Inquiry policy: update allowed for creator or assigned_to user.

---

## 2026-03-17 (Entry 2)

- Services index: added `app-card-grid--services` to use 4-column grid on desktop while keeping global app-card-grid defaults.
- Services index: removed filter sidebar for a cleaner module overview layout.
- Standardized input width inside app-card for all inputs/selects (min 50%, max 100%) while excluding textarea.
- Standardized padding/typography for inputs/selects inside app-card to keep form UI consistent.
- Enforced app-card input/select width standards with higher priority to neutralize custom width utilities.
- Extended standardized input/select width rules to all forms across the app (global form-level enforcement).
- Normalized Blade form inputs/selects to use `app-input` and removed custom width/padding/border classes across modules.
- Forced Google Maps URL inputs to full width (100%) to improve usability.
- Updated all Google Maps URL fields to full-width layout with Auto Fill button stacked below for consistent UI.
- Destinations form: Google Maps URL field now uses inline Auto Fill button beside the input.
- Destinations index: added per_page filter (10/25/50/100) with controller support.
- Destinations index/detail: F&B tidak ditampilkan terpisah karena sudah diwakili oleh Vendor.
- Destinations detail: moved Linked Modules card below header and renamed to Services Availability.
- Destinations detail: Services Availability now uses the same stats style as Inquiries.
- Standardized stats icons globally to use Font Awesome mapping in `<x-index-stats>`.
- Vendors: added soft delete, Deactivate/Activate toggle, and block delete when linked to Activities/F&B.
- Food & Beverages: added `markup_type` + `markup` fields (with backfill from legacy publish-contract delta).
- Food & Beverages: publish rate is now auto-calculated server-side from contract rate + markup (fixed/percent).
- Food & Beverages: create/edit form now includes Markup Type + Markup and readonly auto Publish Rate.
- Food & Beverages: standardized `contract_rate`, `markup`, `publish_rate` to zero-decimal precision (`DECIMAL(15,0)`).
- Food & Beverages: index list now displays Contract, Markup, and Publish rates consistently.
- Tourist Attractions: added `markup_type` + `markup` fields (with backfill from legacy publish-contract delta).
- Tourist Attractions: publish rate now auto-calculated server-side from contract rate + markup (fixed/percent).
- Tourist Attractions: create/edit form now includes Markup Type + Markup and readonly auto Publish Rate.
- Tourist Attractions: standardized `contract_rate_per_pax`, `markup`, `publish_rate_per_pax` to zero-decimal precision (`DECIMAL(15,0)`).
- Tourist Attractions: avoided `Number::format` usage in markup display to prevent `intl` extension dependency error.
- Activities: added adult/child markup fields (`adult_markup_type`, `adult_markup`, `child_markup_type`, `child_markup`) with backfill from legacy publish-contract deltas.
- Activities: adult/child publish rates now auto-calculated server-side from respective contract rate + markup (fixed/percent).
- Activities: create/edit form now includes adult/child markup type + markup, and publish rates are readonly auto-calculated.
- Activities: standardized activity pricing rates to zero-decimal precision (`DECIMAL(15,0)`) for `contract_price`, adult/child contract, adult/child markup, and adult/child publish.
- Activities: markup display uses native PHP number formatting (no `Number::format`) to avoid `intl` extension dependency.

- Hotels (Prices step): added `markup_type` and auto `publish_rate` fields per seasonal room rate.
- Hotels: publish rate now auto-calculated from contract rate + markup (fixed/percent) on frontend and backend.
- Hotels: standardized hotel price rates to `DECIMAL(15,0)` (`contract_rate`, `markup`, `publish_rate`, `kick_back`) for zero-decimal consistency.
- Hotels: hotel detail rates table now shows Contract, Markup, and Publish rates for transparency.
- Hotels: implementation avoids `Number::format` so create/edit flows remain safe without PHP `intl` extension.
----------------------------------------------------------------------------------------------------

END OF ROADMAP

Note:
- Historical entries preserve original wording and file paths at the time they were logged.
