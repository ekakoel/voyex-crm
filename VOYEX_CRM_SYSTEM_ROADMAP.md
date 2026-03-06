# VOYEX CRM -- SYSTEM ROADMAP

Version: 1.0\
Generated: 2026-03-02 02:34:20 UTC

Legend: - ✅ Implemented\
- 🟡 Partial / Basic\
- 🔴 Not Implemented\
- 🔵 Planned / Future Enhancement

------------------------------------------------------------------------

# PHASE 1 -- CORE CRM FOUNDATION

## Authentication & Access Control

  Feature             Status   Notes
  ------------------- -------- ------------------------
  Login / Logout      ✅       Laravel auth
  Role Management     🟡       Basic structure
  Permission Matrix   🟡       Needs granular control
  Access Matrix       🔴       UI not completed
  User Activity Log   🔴       Audit trail needed

------------------------------------------------------------------------

## Customer & Inquiry

  Feature                   Status
  ------------------------- --------
  Customer CRUD             ✅
  Agent (B2B)               🟡
  Inquiry Creation          ✅
  Inquiry Assignment        🟡
  Inquiry Status Tracking   🟡
  Follow-up Reminder        🔴
  Inquiry History Log       🔴

------------------------------------------------------------------------

# PHASE 2 -- ITINERARY & SERVICE ENGINE

## Master Data Services

  Data                  Status
  --------------------- --------
  Tourist Attractions   🟡
  Activities            🟡
  Accommodations        🟡
  Transports            🟡
  Vendors               🟡
  Seasonal Pricing      🔴
  Contract Rate Logic   🔴

## Itinerary Builder

  Feature                  Status
  ------------------------ --------
  Multi-day Structure      🟡
  Day Mapping              🟡
  Drag & Drop Builder      🔴
  Auto Price Calculation   🔴
  Itinerary Template       🔴
  Versioning               🔴

------------------------------------------------------------------------

# PHASE 3 -- QUOTATION SYSTEM

  Feature                 Status
  ----------------------- --------
  Create Quotation        🟡
  Versioning              🟡
  Auto Number Generator   ✅
  Price Calculation       🟡
  Discount Workflow       🔴
  Approval Matrix         🔴
  Quotation Template      🟡
  PDF Generator           🟡
  Margin Calculation      🔴

------------------------------------------------------------------------

# PHASE 4 -- BOOKING MANAGEMENT

  Feature                        Status
  ------------------------------ --------
  Convert Quotation → Booking    🟡
  Participant Management         🔴
  Document Upload                🔴
  Booking Status Workflow        🟡
  Operational Checklist          🔴
  Vendor Confirmation Tracking   🔴

------------------------------------------------------------------------

# PHASE 5 -- INVOICE & FINANCIAL ENGINE

  Feature                  Status
  ------------------------ --------
  Generate Invoice         🟡
  Invoice Number           ✅
  Partial Payment          🔴
  Payment Tracking         🟡
  Expense Input            🟡
  Profit per Booking       🔴
  Commission Calculation   🔴
  Financial Reports        🔴

------------------------------------------------------------------------

# PHASE 6 -- REPORTING & ANALYTICS

  Feature                 Status
  ----------------------- --------
  Sales per Agent         🔴
  Conversion Rate         🔴
  Revenue Dashboard       🟡
  Booking Trend           🔴
  Customer Acquisition    🔴
  Vendor Performance      🔴
  Custom Report Builder   🔴

------------------------------------------------------------------------

# PHASE 7 -- SAAS & SCALABILITY

  Feature                     Status
  --------------------------- --------
  Multi-Tenant Architecture   🔵
  Subscription Management     🔵
  Manual Activation Mode      🔵
  Usage Limitation            🔵
  Tenant Isolation            🔵
  Central Super Admin Panel   🔵

------------------------------------------------------------------------

# PHASE 8 -- AUTOMATION & INTEGRATION

  Feature                Status
  ---------------------- --------
  Email Integration      🟡
  WhatsApp Integration   🟡
  Payment Gateway        🔵
  Google Calendar Sync   🔵
  SMS Notification       🔵
  Auto Reminder Engine   🔴

------------------------------------------------------------------------

# CRITICAL PRIORITY

1.  Approval Workflow (Quotation)
2.  Margin & Profit Calculation
3.  Structured Itinerary Engine
4.  Expense → Profit Linking
5.  Audit Trail System
6.  Participant Management
7.  Auto Reminder Engine

------------------------------------------------------------------------

# LONG TERM VISION

VOYEX CRM → Travel Business Operating System\
Scalable SaaS Travel Platform\
Revenue Optimization Engine

------------------------------------------------------------------------

END OF ROADMAP

------------------------------------------------------------------------

# ROADMAP STATUS UPDATE LOG

Date: 2026-03-02

Completed in this cycle:

1. Super Admin dashboard upgraded with module-centric control layout
   Status: Implemented
   Notes:
   - Added grouped module control center by domain.
   - Added module health, permission coverage, and quick actions.

2. Introduced role "Admin User" as company administrator scope
   Status: Implemented
   Notes:
   - Added role seeding and default permission mapping.
   - Limited system-level modules (module service manager, role manager, access matrix).
   - Aligned sidebar and dashboard redirection behavior.

3. Admin dashboard aligned to "Admin User" responsibilities
   Status: Implemented
   Notes:
   - Added company governance panel, team stats, managed module summary.
   - Added role-aware quick actions and accessible module cards.
   - Accessible module cards now navigate directly to module pages.
   - Disabled module cards now route to module management for faster recovery.

4. Super Admin account isolation from User Manager flows
   Status: Implemented
   Notes:
   - Super Admin accounts are hidden from user listings.
   - Super Admin role is blocked from role assignment in create/update user forms.
   - Added route middleware guard to block direct target access for Super Admin users.
   - Added model helper methods to enforce centralized filtering rules.

5. Inquiry list now exposes itinerary availability status
   Status: Implemented
   Notes:
   - Added itinerary-aware filter on inquiry list (available / missing).
   - Added itinerary status column and badges in desktop + mobile inquiry list.
   - Added quick action "Create Itinerary" directly from inquiry rows when itinerary is missing.

6. Inquiry itinerary labels now support direct navigation
   Status: Implemented
   Notes:
   - Inquiry list itinerary titles are now clickable to itinerary detail pages.
   - Link rendering is permission-aware and falls back to plain text when access is restricted.

7. Itinerary now supports accommodation attachment
   Status: Implemented
   Notes:
   - Added accommodation selection on itinerary create/edit forms.
   - Added many-to-many persistence between itinerary and accommodation data.
   - Added accommodation summary section on itinerary detail page.

8. Itinerary accommodation planning upgraded to day/night scheduling
   Status: Implemented
   Notes:
   - Added accommodation stay planner with day number and night count per entry.
   - Added validation for itinerary day-range consistency and duplicate day assignments.
   - Added pivot schema support for day_number and night_count on itinerary accommodation linkage.

9. Itinerary destination-driven filtering
   Status: Implemented
   Notes:
   - Added destination input on itinerary create/edit.
   - Attraction, activity, and accommodation options are filtered by destination (city/province).
   - Destination is now displayed on itinerary detail.

10. Itinerary destination autocomplete from master data
    Status: Implemented
    Notes:
    - Destination input now uses autocomplete options sourced from core master modules.
    - Option source includes tourist attractions, accommodations, vendors, and transports.
    - Reduces destination typo risk and improves consistency across itinerary records.

11. Itinerary destination upgraded to async searchable dropdown
    Status: Implemented
    Notes:
    - Replaced static datalist with AJAX-based searchable dropdown on itinerary form.
    - Added destination suggestion endpoint with keyword search and result limit.
    - Removed destination preload on create/edit to keep form performant on large master datasets.

12. Itinerary form labeling improvements (create/edit)
    Status: Implemented
    Notes:
    - Added clearer field labels on accommodation stay rows (accommodation, start day, nights, action).
    - Added clearer field labels on schedule rows (item type, attraction/activity selector, start/end time, action).
    - Improves usability for operational users during itinerary create and update flows.

13. Itinerary flow anchors: accommodation-first with optional airport shuttle start/end
    Status: Implemented
    Notes:
    - Removed manual start day input for accommodation; day sequence is now auto-derived from stay order and nights.
    - Added optional arrival/departure airport shuttle selectors on itinerary form.
    - Map route now includes accommodation location anchors per day and supports shuttle start/end points.

14. Itinerary day/night flow refinement with per-day accommodation
    Status: Implemented
    Notes:
    - Added `duration_nights` field and validation aligned with duration days.
    - Accommodation input moved into each day block before schedule items for clearer operational flow.
    - Form now auto-builds `accommodation_stays` payload from per-day accommodation selections.

15. Daily itinerary closing point aligned to accommodation
    Status: Implemented
    Notes:
    - Updated day route logic so each day ends at selected accommodation location.
    - On final day, departure shuttle is now fallback only when accommodation is not set.

16. Day UI endpoint badge for faster operational review
    Status: Implemented
    Notes:
    - Added per-day badge text `Ends at: {Accommodation}` directly in itinerary builder.
    - Badge updates in real-time when day accommodation selection changes.

17. Per-day start/end route points in itinerary builder
    Status: Implemented
    Notes:
    - Added explicit `Start Point` and `End Point` inputs on each day.
    - Day flow now supports arrival airport/manual pickup at start and accommodation/departure at end.
    - Route preview and accommodation stay payload are generated from per-day end points.

18. Client-side validation for required day end point
    Status: Implemented
    Notes:
    - Added UX validation to block submit when any day has empty `End Point`.
    - Invalid day end-point fields are highlighted and the first invalid day is auto-scrolled into view.

19. Start-point travel input to first itinerary item
    Status: Implemented
    Notes:
    - Added per-day input `Travel to next item (minutes)` between start point and schedule items.
    - Value is used for time calculation from start point to first attraction/activity and reflected on map route labels.

20. Day-specific point label synchronization fix
    Status: Implemented
    Notes:
    - Fixed day label sync so `Day 1 Start Point/End Point` no longer appears on cloned day sections.
    - Start/end point labels and field names now always follow each section day index.

21. Start/End point type-item workflow with Airport module
    Status: Implemented
    Notes:
    - Day start/end point now uses two-step selection: choose type first, then choose item.
    - Added airport master module (CRUD) and integrated airport data into itinerary point selection.
    - Start/end point item options are filtered by itinerary destination for consistency and typo prevention.

22. Simplified point item labels on itinerary form
    Status: Implemented
    Notes:
    - Removed type prefix text (e.g., `[Accommodation]`, `[Airport]`) from day start/end point item options.
    - Removed extra inline info text in options so users see clean item names only.
    - Endpoint badge now reads selected item text directly without string splitting assumptions.

23. Destination master module and cross-module location grouping
    Status: Implemented
    Notes:
    - Added new `Destinations` module (CRUD) with module toggle, permission, route, and sidebar integration.
    - Linked location-based modules (`vendors`, `accommodations`, `tourist_attractions`, `airports`, `transports`) to destination via `destination_id`.
    - Added destination selector on related module forms and backfill logic from city/province for existing + seeded data.
    - Updated Admin/Super Admin dashboard module mapping and metrics to include destination governance visibility.

24. Destination standardization by province
    Status: Implemented
    Notes:
    - Destination identity is now standardized by `province` as the main grouping key.
    - Backfill and initial relation mapping now resolve destination primarily from province (fallback to city only if province is empty).
    - Itinerary destination suggestion source is now focused on province-level values for consistency.

25. Vendor destination auto-create from Google Maps URL
    Status: Implemented
    Notes:
    - On vendor create/update, if `destination_id` is empty, system now resolves location from Google Maps URL (via coordinates + reverse geocode).
    - Province result is used to auto-find existing destination; if not found, destination is created automatically.
    - `destination_id` is then assigned automatically to keep vendor data consistent with destination master data.

26. Standardized activity type selection in activity create/update
    Status: Implemented
    Notes:
    - Replaced free-text `activity_type` input with predefined select options for common operational activity categories.
    - Added backend validation whitelist (`Rule::in`) to enforce consistent activity type values.
    - Included safe fallback option `other` and preserved legacy value during edit if existing data is outside current preset list.

27. User-friendly activity type filter labels on activity list
    Status: Implemented
    Notes:
    - Activity type filter options now use human-readable labels (no underscore).
    - Filter option order is synchronized with standardized activity type sequence, then legacy unknown types.
    - Activity type display in list table now also uses formatted labels for consistency.

28. Activity create/update validation stability fix
    Status: Implemented
    Notes:
    - Fixed false invalid error for `activity_type` on update by allowing existing legacy type values.
    - Improved existing gallery image detection on update to support normalized JSON/string payloads.
    - Prevents `gallery_images required` error on update when images are already stored.

29. Legacy activity type hint on edit UI
    Status: Implemented
    Notes:
    - Edit activity form now shows warning hint when selected `activity_type` is a legacy value.
    - Legacy options are explicitly marked with `(Legacy)` in the activity type dropdown.
    - Helps admin migrate old custom types to standardized activity type taxonomy.

30. Global required-field asterisk indicator across forms
    Status: Implemented
    Notes:
    - Added global UI enhancer in master layout to append `*` on labels for inputs with `required` attribute.
    - Covers `input`, `select`, and `textarea`, including dynamically added form fields.
    - Prevents duplicate markers and skips hidden/disabled fields for cleaner UX.

31. Standardized Google Maps-based location autofill across location modules
    Status: Implemented
    Notes:
    - Added centralized `LocationResolver` service + AJAX endpoint to resolve Google Maps URL into location metadata.
    - Standardized location input UX (`Google Maps URL + Auto Fill`) in Destination, Accommodation, Vendor, Airport, Transport, and Tourist Attraction forms.
    - Autofill now supports `country`, `destination`, `location`, `city`, `province`, `address`, `latitude`, `longitude`, and `timezone` fields.
    - Added schema support for missing standardized location fields across modules and integrated backend fallback autofill on save.

32. Airport module simplification: remove IATA/ICAO fields
    Status: Implemented
    Notes:
    - Removed IATA/ICAO from Airport form, list, detail, and backend validation/search flow.
    - Removed Airport model and itinerary query dependencies on `iata_code` / `icao_code`.
    - Added migration to drop `iata_code` and `icao_code` columns from `airports` table.

33. Destination province master seeder (Indonesia)
    Status: Implemented
    Notes:
    - Added `DestinationProvinceSeeder` with full province list and capital-city mapping as destination baseline.
    - Seeder uses `updateOrCreate` by province, sets destination name to province, country to Indonesia, and timezone by WIB/WITA/WIT region.
    - Registered seeder in `DatabaseSeeder` before destination backfill so location-based modules map to standardized destination records.

34. VendorController persistence guard for schema mismatch
    Status: Implemented
    Notes:
    - Added runtime column filtering before vendor create/update to persist only fields that exist in current `vendors` table schema.
    - Prevents SQL unknown-column failures when deployment schema is temporarily behind controller/form changes.
    - Keeps location auto-fill flow functional while maintaining backward compatibility during staged migrations.
