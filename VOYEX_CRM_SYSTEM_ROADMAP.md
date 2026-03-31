# VOYEX CRM -- SYSTEM ROADMAP

Version: 1.2  
Last Updated: 2026-03-30

Legend:  
- DONE = Implemented  
- PARTIAL = Basic/limited, needs improvement  
- TODO = Not implemented  
- FUTURE = Planned for later phase  

----------------------------------------------------------------------------------------------------

# PHASE 1 -- CORE CRM FOUNDATION

## Authentication & Access Control

Feature | Status | Notes
--- | --- | ---
Login / Logout | DONE | Laravel auth + Breeze
Role Management | DONE | CRUD role, clone, template role, cache refresh
Permission Matrix | DONE | Per-module Access + CRUD, role template, counters, auto-sync
Access Matrix | DONE | Super Admin access matrix screen
Module Toggle (Service Manager) | DONE | Module enable/disable control
CRUD Permission Enforcement | DONE | `module.permission:{moduleKey}` middleware
Friendly 403 Page | DONE | User-friendly 403 with actions
User Activity Log | PARTIAL | Activity log UI exists, full audit trail not yet

## Customer & Inquiry

Feature | Status | Notes
--- | --- | ---
Customer CRUD | DONE | Includes import
Agent (B2B) | PARTIAL | Supported in data model, UX still basic
Inquiry Creation | DONE | Includes inquiry number auto-format
Inquiry Assignment | DONE | Assign to Manager/Marketing
Inquiry Status Tracking | DONE | Unified statuses + Final lock
Inquiry Deactivate/Activate | DONE | Soft delete on inquiries, toggle status in index
Follow-up Reminder | TODO | Auto reminder not yet
Inquiry History Log | PARTIAL | Communications & follow-ups exist

----------------------------------------------------------------------------------------------------

# PHASE 2 -- ITINERARY & SERVICE ENGINE

## Master Data Services

Feature | Status | Notes
--- | --- | ---
Destinations CRUD | DONE | Destination master + province seeder
Vendors CRUD | DONE | Google Maps autofill
Activities CRUD | DONE | Standardized activity types
Food & Beverage CRUD | DONE | Standard forms
Accommodations CRUD | DONE | Standard forms
Hotels CRUD | PARTIAL | Admin CRUD + base UI, pricing/promos input added
Airports CRUD | DONE | Simplified fields
Transports CRUD | DONE | Standard forms
Tourist Attractions CRUD | DONE | Standard forms
Soft Delete / Deactivate Toggle | DONE | Master data modules use soft delete + activate toggle
Location Autofill | DONE | Shared resolver for modules
Seasonal Pricing | TODO | Not implemented
Contract Rate Logic | TODO | Not implemented

## Itinerary Builder

Feature | Status | Notes
--- | --- | ---
Multi-day Structure | DONE | Day-based planner
Day Mapping | DONE | Start/End points
Accommodation Planning | DONE | Day/night planning
Destination Filtering | DONE | Filtered options by destination
PDF Generation | DONE | Itinerary PDF route
Drag & Drop Builder | TODO | Not implemented
Auto Price Calculation | TODO | Not implemented
Itinerary Template | TODO | Not implemented
Versioning | TODO | Not implemented

----------------------------------------------------------------------------------------------------

# PHASE 3 -- QUOTATION SYSTEM

Feature | Status | Notes
--- | --- | ---
Create Quotation | DONE | Full CRUD
Auto Number Generator | DONE | Quotation number
Price Calculation | DONE | Final amount calc
Discount Workflow | DONE | Discount + approval guard
Approval Workflow | DONE | Approve/Reject/Pending
Quotation Template | DONE | Template support
PDF Generator | DONE | PDF export
CSV Export | DONE | CSV export
Versioning | TODO | Not implemented
Margin Calculation | TODO | Not implemented

----------------------------------------------------------------------------------------------------

# PHASE 4 -- BOOKING MANAGEMENT

Feature | Status | Notes
--- | --- | ---
Convert Quotation → Booking | DONE | Booking creation flow
Booking Status Workflow | DONE | Status + Final lock
CSV Export | DONE | Export from bookings
Participant Management | TODO | Not implemented
Document Upload | TODO | Not implemented
Operational Checklist | TODO | Not implemented
Vendor Confirmation Tracking | TODO | Not implemented

----------------------------------------------------------------------------------------------------

# PHASE 5 -- INVOICE & FINANCIAL ENGINE

Feature | Status | Notes
--- | --- | ---
Generate Invoice | DONE | Invoice service + UI
Invoice Number | DONE | Auto numbering
Invoice Status Workflow | DONE | Status + Final lock
Payment Tracking | PARTIAL | Basic status + paid_at
Expense Input | TODO | Not implemented
Profit per Booking | TODO | Not implemented
Commission Calculation | TODO | Not implemented
Financial Reports | TODO | Not implemented

----------------------------------------------------------------------------------------------------

# PHASE 6 -- REPORTING & ANALYTICS

Feature | Status | Notes
--- | --- | ---
Role Dashboards | DONE | Separate dashboard per role (except Super Admin)
Revenue Dashboard | PARTIAL | KPI only, no advanced analytics
Conversion Rate | PARTIAL | Basic KPI on Manager/Director
Booking Trend | TODO | Not implemented
Sales per Agent | TODO | Not implemented
Customer Acquisition | TODO | Not implemented
Vendor Performance | TODO | Not implemented
Custom Report Builder | TODO | Not implemented

----------------------------------------------------------------------------------------------------

# PHASE 7 -- SAAS & SCALABILITY

Feature | Status | Notes
--- | --- | ---
Multi-Tenant Architecture | FUTURE | Planned
Subscription Management | FUTURE | Planned
Manual Activation Mode | FUTURE | Planned
Usage Limitation | FUTURE | Planned
Tenant Isolation | FUTURE | Planned
Central Super Admin Panel | FUTURE | Planned

----------------------------------------------------------------------------------------------------

# PHASE 8 -- AUTOMATION & INTEGRATION

Feature | Status | Notes
--- | --- | ---
Email Integration | TODO | Not implemented
WhatsApp Integration | TODO | Not implemented
Payment Gateway | FUTURE | Planned
Google Calendar Sync | FUTURE | Planned
SMS Notification | FUTURE | Planned
Auto Reminder Engine | TODO | Not implemented

----------------------------------------------------------------------------------------------------

# CRITICAL PRIORITY (NEXT)

1. Approval Workflow (Quotation) — DONE (basic), enhance matrix + audit
2. Margin & Profit Calculation — TODO
3. Structured Itinerary Engine — DONE (basic), needs pricing + templates
4. Expense → Profit Linking — TODO
5. Audit Trail System — PARTIAL (activity logs only)
6. Participant Management — TODO
7. Auto Reminder Engine — TODO

----------------------------------------------------------------------------------------------------

# MANDATORY CHANGE LOG & DOCUMENTATION POLICY (REQUIRED)

Kebijakan ini wajib untuk setiap update code (penambahan, perubahan, pengurangan) di project VOYEX CRM.

1. Setiap perubahan code WAJIB dicatat di bagian `# CHANGELOG (LATEST)` pada file ini.
2. Setiap perubahan code WAJIB memperbarui minimal 1 file dokumentasi `.md` yang paling relevan dengan scope perubahan.
3. Jika perubahan berdampak lintas modul, WAJIB update juga:
   - `PROJECT_KNOWLEDGE_BASE.md` (ringkasan source of truth),
   - dan dokumen modul/fitur terkait (contoh: itinerary map, flow create/edit, dsb).
4. Pull/merge dianggap belum selesai jika:
   - perubahan code ada tetapi changelog roadmap belum diupdate, atau
   - tidak ada update dokumentasi teknis yang relevan.
5. Catatan changelog minimal harus memuat:
   - tanggal,
   - area/module yang diubah,
   - ringkasan perubahan,
   - dampak perilaku sistem,
   - catatan QA singkat (jika ada).

----------------------------------------------------------------------------------------------------

# CHANGELOG (LATEST)

Date: 2026-03-30
Completed in this cycle:

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

Date: 2026-03-23
Completed in this cycle:

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



Date: 2026-03-13
Completed in this cycle:

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

Date: 2026-03-17
Completed in this cycle:

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

Date: 2026-03-16
Completed in this cycle:

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

Date: 2026-03-17
Completed in this cycle:

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








