# VOYEX CRM -- SYSTEM ROADMAP

Version: 1.1  
Last Updated: 2026-03-17

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

# CHANGELOG (LATEST)

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

----------------------------------------------------------------------------------------------------

END OF ROADMAP
