# VOYEX CRM -- SYSTEM ROADMAP

Version: 1.4  
Last Updated: 2026-04-17

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
Contract Rate Logic | DONE | Quotation validation + source master sync implemented

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
Quotation Template | REMOVED | Legacy template table removed from current architecture
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

Date: 2026-04-17
Completed in this cycle:

- Documentation full-sync (all markdown):
  - refreshed root source-of-truth docs (`README.md`, `PROJECT_GUIDELINES.md`, `PROJECT_KNOWLEDGE_BASE.md`, `VOYEX_CRM_AI_GUIDELINE.md`).
  - refreshed technical docs index (`docs/README.md`) and added consistent `Last Updated` metadata across markdown files.
  - updated approval and validation UAT matrices to match current behavior:
    - approval is permission-first with minimum two non-creator approvals,
    - validation flow is AJAX per-row and final validation visibility at 100% progress.

- Access control documentation alignment:
  - documented permission-first strategy for CRUD and non-CRUD actions.
  - documented dashboard redirect priority including `dashboard.superadmin.view`.
  - documented service-manager routes enforcement with `module.permission`.

- Database safety documentation hardening:
  - added explicit rule that testing must use isolated `.env.testing` database.
  - added prohibition notes for destructive commands on primary database (`migrate:fresh`, `db:wipe`, `migrate:refresh`).

- Developer superadmin bypass hardening:
  - `superadmin@example.com` is now treated as superadmin identity even without role binding.
  - centralized bypass through `User::isSuperAdmin()` and wired to Gate + permission/module middlewares + sidebar/role guards.
  - impact: developer account keeps full system access across pages/features/functions without dependency on role/permission records.

- System Modules strict enforcement hardening:
  - module toggle is now enforced universally (including superadmin identity) for access paths guarded by module/permission middleware.
  - disabled module now returns `404` from route access checks (`module`, `module.permission`, and `permission:module.*` pathways).
  - sidebar menu now consistently hides disabled modules for all users.
  - quotation approval notification widget now auto-hides when quotations module is disabled.
  - `service_manager` module is protected from disable action to prevent lockout of System Modules control center.

- Company branding asset activation fix:
  - fixed missing `public/storage` symlink by running `php artisan storage:link`, enabling browser access to uploaded files from `storage/app/public`.
  - master layout now renders uploaded company logo in sidebar header when `company_settings.logo_path` is available.
  - global `application-logo` component now uses uploaded company logo as primary source with SVG fallback.
  - guest layout now uses company name + uploaded favicon for page title/head branding consistency.
  - setup quick-start updated to include `php artisan storage:link`.
  - image public URL resolver for local `public` disk now returns relative `/storage/...` path (instead of host-bound URL), preventing host/port mismatch issues (for example `localhost` vs `localhost:8000`) that caused broken favicon/logo previews.

- Role & Permission coverage expansion for admin tools:
  - added dedicated permissions:
    - `services.map.view` (Service Map page),
    - `superadmin.access_matrix.view` (Access Matrix page).
  - wired route guards:
    - `services.map` now requires `services.map.view`,
    - `superadmin.access-matrix` now requires `superadmin.access_matrix.view`.
  - updated sidebar access checks to use these dedicated permissions.
  - added migration `2026_04_17_120000_add_service_map_and_access_matrix_permissions.php` so existing databases receive both permissions without full reseed.
  - improved role-form UI labels for these permissions:
    - `services.map.view` => `View Service Map`,
    - `superadmin.access_matrix.view` => `View Access Matrix`.
  - fixed route guard mismatch causing false `403` after enabling view permissions:
    - `services.map` now requires only `services.map.view` (plus module enabled),
    - `superadmin.access-matrix` accepts `superadmin.access_matrix.view` with legacy fallback `dashboard.superadmin.view`.

Date: 2026-04-16
Completed in this cycle:

- Itinerary airport filter accuracy fix (destination-based):
  - updated start/end point item destination matching from broad substring matching to token-based exact-word matching.
  - impact example:
    - destination `Bali` now matches Bali-related airport entries,
    - no longer incorrectly matches `Balikpapan`.
  - applied to day-point option filtering logic used by `Select start point item` and `Select end point item`.

- Itinerary day action rollback:
  - reverted `+ Add Item` placement back to original top-right position in day header row.
  - removed custom action-row relocation script that caused add-item layout instability.

- Itinerary day action layout refinement:
  - moved `+ Add Item` button to align with `Estimated travel time to end point (minutes)` row.
  - enforced placement order per day section:
    - schedule items list,
    - then travel-time input + `+ Add Item` inline on the right,
    - then `Day N End Point`.
  - button now stays on the right side of the travel-time input (always inline beside input).
  - button style updated to primary and height aligned with travel-time input control.

- Itinerary schedule wording update:
  - updated travel connector placeholder texts:
    - `Travel to next item (minutes)` -> `Estimated travel time to the next item (minutes)`
    - `Travel to End Point (minutes)` -> `Estimated travel time to end point (minutes)`.

- Itinerary transport unit option label refinement:
  - updated `Day N Transport Unit` select option text to `Brand Name (Seat Capacity)`.
  - brand source uses transport brand name when available, with safe fallback to unit name.

- Itinerary schedule-point select label simplification:
  - `Day N Start Point` and `Day N End Point` item select now show hotel option text as hotel name only.
  - `Select room` options now show room name only (without hotel prefix/view suffix).
  - applied to both start-point and end-point selectors in shared itinerary create/edit form.

- Itinerary day-point self-booked hotel flexibility (create/edit):
  - updated schedule day-point validation so `Start Point` / `End Point` item and room are no longer mandatory when point type is `hotel` and booking mode is `self`.
  - retained strict validation for arranged-hotel flow:
    - hotel item remains required,
    - room remains required,
    - room-hotel consistency is still enforced.
  - frontend behavior aligned in itinerary form:
    - when booking mode is `self`, point item becomes optional,
    - room selector/count are auto-disabled and can stay empty,
    - switching booking mode immediately re-syncs field required/disabled state.
  - applied on both create and edit itinerary pages (shared `_form` script).

- Global currency-input standard enforcement (all modules/pages):
  - enforced money-field detection globally in master layout for monetary inputs (`contract_rate`, `publish_rate`, `unit_price`, `markup`, `discount`, `total`, etc).
  - any detected monetary input now auto-applies:
    - left currency badge affix,
    - right-aligned numeric value,
    - grouped display formatting,
    - numeric-only normalization before submit.
  - standardized markup badge behavior globally:
    - `%` when paired `markup_type` is `percent`,
    - active currency symbol/code when `fixed`.
  - impact:
    - standard now applies beyond quotation validation and covers all pages using nominal inputs (including legacy plain inputs that were not yet wrapped by `x-money-input`).

- Project-wide nominal input standard formalization:
  - established `x-money-input` as mandatory component for nominal fields (price/rate/amount/fee/cost/discount/total).
  - locked UI convention: currency badge must use left affix (not right affix).
  - standardized nominal behavior:
    - display with readable grouped format,
    - submit payload normalized as numeric-only value.
  - documented markup badge contract:
    - `%` for percent markup,
    - active currency badge for fixed markup.
- Documentation updates to prevent standard drift:
  - updated `PROJECT_GUIDELINES.md` with mandatory `Standar Input Nominal`.
  - updated `VOYEX_CRM_AI_GUIDELINE.md` with nominal-input guard.
  - updated `PROJECT_KNOWLEDGE_BASE.md` section 7 with project-level nominal-input baseline.
  - updated `docs/core/LAYOUT_GUIDE.md` anti-drift section to include nominal standard.
  - added technical source-of-truth doc `docs/technical/NOMINAL_INPUT_STANDARD.md`.
  - updated `docs/README.md` technical map to include nominal-input standard reference.

Date: 2026-04-16
Completed in this cycle:

- Global money-input affix alignment:
  - standardized `x-money-input` component to render currency badge on left affix by default.
  - updated shared money-input CSS compatibility so quotation item layouts remain aligned for both left/right affix classes during transition.
  - impact scope: all pages using `resources/views/components/money-input.blade.php` now inherit left-side currency badge behavior.

- Quotation validation money-input badge standardization:
  - aligned validation-page nominal inputs with create/edit quotation money-input pattern.
  - adjusted badge placement to left affix on validation nominal inputs to avoid overlay with long numeric values.
  - added currency/code badge for contract-rate inputs in mobile and desktop variants.
  - added dynamic markup badge behavior:
    - `%` when markup type is `percent`,
    - active currency badge (`Rp/$/code`) when markup type is `fixed`.
  - implemented sync helpers in validation JS:
    - `refreshAllMoneyBadges()`,
    - `refreshMarkupBadgesForItem(itemId)`.

- Quotation validation currency alignment:
  - validation modal and validation item price display now follow active app currency (`window.appCurrency`) including symbol and locale formatting.
  - added IDR-to-display and display-to-IDR conversion in validation page JS so:
    - user sees values in selected currency,
    - submitted payload remains normalized in IDR for backend integrity.
  - updated `resources/views/modules/quotations/validate.blade.php` currency formatter and submit normalization flow for both AJAX save-item and save-progress actions.

- Quotation revalidation access rule update:
  - validation page access is now kept available for validation actors even after validation status reaches `valid`.
  - access is locked only when quotation status becomes `approved` or `final`.
  - implemented by aligning `canValidateQuotation` flag logic in:
    - `app/Http/Controllers/Sales/QuotationController.php` (`show` and `edit`).

- Quotation sidebar visibility enhancement (edit/detail):
  - added dedicated `Validation Progress` card in quotation edit and detail sidebar.
  - card now shows validation status, required item count, validated item count, progress percentage, and progress bar.
  - includes direct CTA to `Validate Quotation` page when user has validation access.

- Quotation Validation responsive standardization:
  - validation page now uses standardized responsive pattern:
    - mobile/tablet card grouping for validation items,
    - desktop table view (`xl+`) for dense operational data.
  - added reusable global CSS utility classes in `resources/css/app.css`:
    - `responsive-data-shell`, `responsive-data-mobile`, `responsive-data-desktop`,
    - `responsive-group-card`, `responsive-group-header`, `responsive-item-card`,
    - `module-kpi-grid`, `module-action-row`.
  - applied shared utility classes to `resources/views/modules/quotations/validate.blade.php` for KPI grid, grouped list, and action row consistency.
  - updated standards documents so responsive behavior becomes mandatory baseline for existing and future pages:
    - `docs/core/LAYOUT_GUIDE.md`,
    - `PROJECT_GUIDELINES.md`,
    - `VOYEX_CRM_AI_GUIDELINE.md`.

- Quotation validation UX refinement (high-usage operational flow):
  - validation table grouped by `Day N` using section rows for better scanability on large quotations.
  - validation table column order standardized to:
    `Mark Validated -> Type -> Vendor/Provider/Item -> Description -> QTY -> Contract Rate -> Markup Type -> Markup -> Validated by -> Validation Status -> Actions`.
  - item labels readability improvements:
    - type labels mapped (`Food and Beverage`, `Tourist Attraction`, `Transport`, `Hotel`).
    - description simplified to item name only.
    - vendor/provider column behavior tuned by type:
      - Activity/Transport/F&B: vendor/provider name,
      - Hotel: hotel name,
      - room detail shown in description.
  - modal trigger moved from description to `Vendor/Provider/Item` column.
  - removed obsolete hint text for description click interaction.
  - modal detail title format updated to:
    `Day N - Item Type - Item Name`,
    including readable item type mapping (`Food and Beverage`, `Tourist Attraction`, `Transport`).
  - modal timestamp formatting updated to:
    `Updated at: yyyy-mm-dd (hh:ii)`.
  - validation row action copy streamlined (`Save Item` => `Validate`) and success inline text noise removed.

- Quotation create/edit hardening and consistency:
  - `Contract Rate`, `Markup Type`, and `Markup` on quotation item rows are now read-only in create/edit to enforce rate governance via validation flow.
  - inquiry detail notes rendering aligned:
    - create page now renders sanitized HTML notes (read-only), matching edit behavior.

- Quotation lifecycle visibility behavior:
  - quotation index now displays active (non-deactivated) data only.
  - `My Quotation` keeps showing both active and deactivated records.
  - deactivated quotations now present status badge as `inactive` in listing/detail views.
  - activate/deactivate action now redirects back to originating page context.

- Documentation updates:
  - updated UAT matrix:
    - `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md`
    to reflect current UI flow (AJAX per-row validate, vendor/provider modal trigger, validate button visibility rule, modal title format).
  - removed unused legacy root markdown files to eliminate stale duplication:
    - `ACTIVITY_LOG_FIX.md`
    - `ANALYSIS_REPORT.md`
    - `CHEAT_SHEET.md`
    - `ITINERARY_CREATE_EDIT_FLOW.md`
    - `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
    - `LAYOUT_GUIDE.md`
    - `modul.md`
    - `PROJECT_AUDIT_ARCHIVE.md`
    - `QUICK_SUMMARY.md`
    - `QUOTATION_APPROVAL_UAT_MATRIX.md`
    - `QUOTATION_VALIDATION_UAT_MATRIX.md` (root copy)
    - `SIDEBAR_COLLAPSE_FIX.md`
    - `TECHNICAL_FIX_NOTES.md`
  - updated documentation map:
    - `README.md` (added validation UAT reference + cleanup rule)
    - `docs/README.md` (canonical maintenance rule and source-of-truth boundary)
    - `PROJECT_KNOWLEDGE_BASE.md` (removed obsolete pointer references)
    - `docs/archive/PROJECT_AUDIT_ARCHIVE.md` (archive note adjusted post-cleanup).

Date: 2026-04-15
Completed in this cycle:

- Quotation validation performance improvement (lazy modal detail API):
  - added JSON endpoint for on-demand item detail in validation page:
    - `GET /quotations/{quotation}/validate/items/{item}/detail-json`
    - controller: `app/Http/Controllers/Sales/QuotationValidationController.php::itemDetailJson(...)`.
  - refactored validation modal rendering on:
    - `resources/views/modules/quotations/validate.blade.php`
    so item detail, vendor/contact, and history are fetched only when description is clicked (no full pre-render for all items).
  - added backend detail payload builder:
    - `app/Services/QuotationValidationService.php::buildValidationItemDetail(...)`.

- Quotation validation UAT documentation rollout:
  - added dedicated UAT matrix:
    - `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md`
    covering access control, save-progress, partial/valid transitions, approval guard, source-rate update checks, audit trail, and performance checkpoint.
  - updated documentation map:
    - `docs/README.md` now includes quotation validation UAT reference.

- QA note:
  - `php -l` passed for updated validation service/controller and Blade wiring.
  - `php artisan test tests/Feature/Modules/QuotationValidationWorkflowTest.php` passed.
  - `php artisan test tests/Feature/Modules/QuotationsGlobalDiscountRoleTest.php` passed.
  - `php artisan view:cache` passed.

Date: 2026-04-13
Completed in this cycle:

- Services map Vite manifest fix:
  - resolved runtime error:
    `Unable to locate file in Vite manifest: resources/js/service-map.js`
    on `modules/services/map`.
  - root cause: `resources/js/service-map.js` was referenced as a standalone Vite entry in Blade, but not registered in Vite input manifest.
  - fix implemented by bundling `service-map.js` through main app bundle:
    - added import in `resources/js/app.js`
    - removed standalone `@vite('resources/js/service-map.js')` from `resources/views/modules/services/map.blade.php`.
- QA note:
  - `php artisan view:cache` passed.
  - local `npm run build` could not be fully executed in current environment due host execution policy/sandbox restriction; deployment environment build is required after pull.

- Quotation lifecycle alignment (Final/Pending synchronization to Itinerary + Inquiry):
  - updated quotation-linked status sync so:
    - `quotation.status = final` forces related `itinerary.status = final` and `inquiry.status = final`,
    - non-final quotation states (including `pending` and `rejected`) force related `itinerary.status = processed` and `inquiry.status = processed`.
  - implemented centralized status propagation in:
    - `app/Http/Controllers/Sales/QuotationController.php` via `syncLinkedLifecycleStatusesForQuotation(...)`.
  - updated itinerary lifecycle logic in `app/Models/Itinerary.php`:
    - removed hard immutable-final behavior so itinerary can return from `final` to `processed` when quotation is no longer `final` (required after reject).
  - enabled reject flow for final quotation by Manager/Director:
    - `reject` no longer blocked by final status,
    - reject now unlocks linked inquiry/itinerary by pushing them to `processed`.
  - tightened lock checks in:
    - `app/Http/Controllers/Sales/InquiryController.php`
    - `app/Http/Controllers/Admin/ItineraryController.php`
    so quotation `approved` or `final` both treated as locked states.
- QA note:
  - `php -l app/Models/Itinerary.php` passed.
  - `php -l app/Http/Controllers/Sales/QuotationController.php` passed.
  - `php -l app/Http/Controllers/Sales/InquiryController.php` passed.
  - `php -l app/Http/Controllers/Admin/ItineraryController.php` passed.
  - `php artisan view:cache` passed.

- Image delivery performance standardization (thumbnail-first + auto-regenerate):
  - introduced centralized thumbnail resolver API in `app/Support/ImageThumbnailGenerator.php`:
    - `resolvePublicUrl(...)` for thumbnail-first rendering,
    - `resolveOriginalPublicUrl(...)` for original fallback,
    - `normalizeStoredPath(...)` for consistent storage path normalization.
  - implemented lazy thumbnail regeneration flow:
    - if thumbnail is missing while image is requested, system generates thumbnail from original and reuses it for subsequent requests.
  - applied this standard to active image-rendering views (auth/company branding + module forms/show pages touched in this cycle), replacing direct storage asset usage in those paths.
  - extended PDF image pipelines in:
    - `app/Http/Controllers/Admin/ItineraryController.php`
    - `app/Http/Controllers/Sales/QuotationController.php`
    so missing thumbnails are regenerated before falling back to original image data URI.
  - added operational backfill command:
    - `php artisan images:generate-thumbnails`
    implemented in `app/Console/Commands/GenerateImageThumbnails.php`.
  - added technical standard document:
    - `docs/technical/IMAGE_THUMBNAIL_STANDARD.md`
    and registered it in `docs/README.md`.
- QA note:
  - `php -l app/Support/ImageThumbnailGenerator.php` passed.
  - `php -l app/Console/Commands/GenerateImageThumbnails.php` passed.
  - `php -l app/Http/View/CompanyBrandComposer.php` passed.
  - `php artisan view:cache` passed.
  - command discovery confirmed for `images:generate-thumbnails`.
  - operational backfill executed successfully:
    - `php artisan images:generate-thumbnails --disk=public --width=360 --height=240`
    - result: processed `1333`, generated `392`, skipped `941`.

- English UI standardization phase-5 (create/edit/show completion + standardization policy):
  - completed translation-key migration for remaining `create/edit/show` pages in modules area, including `company-settings/edit`.
  - converted company settings form labels, helper texts, auth-theme labels, map section title, map hint messages, and action button text into `ui.*` translation keys.
  - updated map location partial (`modules/company-settings/partials/_location-map.blade.php`) so runtime map hints also use translation keys.
  - expanded `lang/en/ui.php` with new shared and module keys required by final cleanup (company settings, itinerary show labels, quotation show statuses, activity/hotel/airport detail labels, etc.).
  - added formal implementation standard document:
    - `docs/technical/I18N_TRANSLATION_STANDARD.md`
    - and registered it in `docs/README.md`.
  - enforcement baseline check executed:
    - all files under `resources/views/modules/**/{create,edit,show}.blade.php` now reference `ui.*` keys,
    - no missing `ui.*` key references detected in those pages.
- QA note:
  - `php -l lang/en/ui.php` passed.
  - `php artisan view:cache` passed after phase-5 translation and standards update.

Date: 2026-04-13
Completed in this cycle:

- English UI standardization phase-4 (non-index UI text cleanup):
  - converted remaining Indonesian UI copy to English across form/create/help/error/email/map-hint surfaces.
  - major areas updated:
    - quotation form status/validation messages and itinerary item sync hints,
    - customer/inquiry form validation/help copy,
    - map interaction hints in vendor/airport/hotel/company settings location widgets,
    - create/edit helper copy in modules (`airports`, `bookings`, `currencies`, `destinations`, `vendors`, `users`, `transports`, `activities`, `tourist-attractions`, `hotels`, `inquiries`, `roles`),
    - itinerary include/exclude placeholders and validation alert copy,
    - role form/create/edit helper text,
    - super admin/admin/finance/editor dashboard subtitle/help copy,
    - follow-up reminder email template,
    - 403 error page subtitle/body/action labels.
  - additional repository-wide scan pass executed to detect Indonesian literals in `resources/views/**/*.blade.php`, then applied targeted cleanup for detected entries.
- QA note:
  - `php -l` passed for all changed PHP/Blade files in this cycle.
  - `php artisan view:cache` passed after phase-4 copy cleanup.

Date: 2026-04-13
Completed in this cycle:

- English UI standardization phase-3 (additional module index rollout):
  - extended translation registry `lang/en/ui.php` for additional shared labels/entities/modules:
    - new common keys (location/country/destination/rates/permissions/clone/duplicate/active/inactive, etc.),
    - new entity keys (currencies, destinations, vendors, airports, transport services, tourist attractions, activities, itineraries, invoices, hotels, roles),
    - new module dictionaries for:
      - `currencies`, `destinations`, `vendors`, `airports`, `transports`,
      - `tourist_attractions`, `activities`, `itineraries`, `invoices`, `hotels`, `roles`.
  - refactored additional index/list pages from hardcoded literals to translation keys:
    - `resources/views/modules/currencies/index.blade.php`,
    - `resources/views/modules/destinations/index.blade.php`,
    - `resources/views/modules/vendors/index.blade.php`,
    - `resources/views/modules/airports/index.blade.php`,
    - `resources/views/modules/transports/index.blade.php`,
    - `resources/views/modules/tourist-attractions/index.blade.php`,
    - `resources/views/modules/tourist-attractions/partials/_index-results.blade.php`,
    - `resources/views/modules/activities/index.blade.php`,
    - `resources/views/modules/activities/partials/_index-results.blade.php`,
    - `resources/views/modules/itineraries/index.blade.php`,
    - `resources/views/modules/invoices/index.blade.php`,
    - `resources/views/modules/hotels/index.blade.php`,
    - `resources/views/modules/hotels/partials/_index-results.blade.php`,
    - `resources/views/modules/roles/index.blade.php`,
    - `resources/views/modules/roles/partials/_index-results.blade.php`.
- QA note:
  - `php -l` passed for all updated Blade files and `lang/en/ui.php`.
  - `php artisan view:cache` passed after phase-3 translation refactor.

Date: 2026-04-13
Completed in this cycle:

- English UI standardization phase-2 (module index rollout):
  - extended translation registry in `lang/en/ui.php` for reusable index/common/entities/module labels:
    - common actions/labels (`reset`, `filters`, `actions`, `status`, `priority`, `deadline`, `assigned`, etc.),
    - index helpers (`per_page_option`, `no_data_available`, `refine_list_quickly`),
    - entity labels (`customers`, `inquiries`, `quotations`, `bookings`, `employees`, `itinerary`),
    - module-specific labels for users, customers, inquiries, quotations, and bookings pages.
  - refactored module list pages to translation keys (replacing hardcoded UI literals):
    - `resources/views/modules/users/index.blade.php`,
    - `resources/views/modules/customers/index.blade.php`,
    - `resources/views/modules/inquiries/index.blade.php`,
    - `resources/views/modules/quotations/index.blade.php`,
    - `resources/views/modules/bookings/index.blade.php`.
  - completed quotations translation cleanup by replacing remaining hardcoded activate/deactivate confirm text with:
    - `ui.modules.quotations.confirm_activate`,
    - `ui.modules.quotations.confirm_deactivate`.
- QA note:
  - `php -l` passed for all updated Blade files and `lang/en/ui.php`.
  - `php artisan view:cache` passed after phase-2 translation refactor.

Date: 2026-04-13
Completed in this cycle:

- English UI standardization phase-1 (translation-key rollout):
  - added new translation registry `lang/en/ui.php` as centralized source for custom UI strings:
    - auth page titles/subtitles/forms/spinner states/messages,
    - common action labels,
    - quotation comments labels/placeholders/empty states.
  - refactored auth views to use translation keys instead of hardcoded literals:
    - `resources/views/auth/login.blade.php`,
    - `resources/views/auth/forgot-password.blade.php`,
    - `resources/views/auth/reset-password.blade.php`.
  - refactored quotation comments panel to use translation keys and pluralized count via `trans_choice()`:
    - `resources/views/partials/_quotation-comments.blade.php`.
  - aligned password-reset feedback messages in controller to translation keys:
    - `app/Http/Controllers/Auth/PasswordResetLinkController.php`.
- QA note:
  - `php -l` passed for updated PHP/Blade files and new language file.
  - `php artisan view:cache` passed after translation refactor.

Date: 2026-04-13
Completed in this cycle:

- Auth submit spinner UX for Login and Forgot Password:
  - added submit-state loading UX on `auth.login` and `auth.forgot-password` forms:
    - prevents double submit during request lifecycle,
    - disables submit button while processing,
    - updates button text (`Signing In...` / `Sending Link...`),
    - shows inline button spinner.
  - added full-page auth overlay spinner (`.auth-page-spinner`) shown on form submit until response/redirect.
  - implemented `pageshow` reset guard so spinner/button state is restored correctly when user navigates back.
- Styling additions in `public/assets/css/auth.css`:
  - `.btn-primary.is-loading`, `.auth-btn-spinner`,
  - `.auth-page-spinner` and its inner ring/text states,
  - `@keyframes auth-spin`.
- QA note:
  - `php -l` passed for updated auth Blade files and auth stylesheet.
  - `php artisan view:cache` passed.

Date: 2026-04-13
Completed in this cycle:

- Auth UI simplification (background image removal):
  - removed left-panel illustration images from `auth.login`, `auth.forgot-password`, and `auth.reset-password`.
  - left panel now relies on gradient/theme styling only (no background image element rendered).
- QA note:
  - `php -l` passed for updated auth Blade files.
  - `php artisan view:cache` passed.

Date: 2026-04-13
Completed in this cycle:

- Auth left-panel visual refresh for Login and Forgot Password:
  - redesigned left-side content to show context-relevant information per page:
    - Login: workflow/value highlights for daily CRM operations.
    - Forgot Password: clear 3-step recovery flow for better UX guidance.
  - modernized auth-left background with layered gradients, soft glass cards, and decorative shapes for a cleaner premium look.
  - improved auth illustration relevance:
    - login uses workflow-themed illustration treatment.
    - forgot-password uses dedicated recovery-support image (`public/assets/images/password-reset-support.jfif`).
  - added new reusable auth-left UI styles in `public/assets/css/auth.css`:
    - `.auth-context-list`, `.auth-context-list--steps`,
    - `.auth-left--login`, `.auth-left--security`,
    - `.auth-illustration--login`, `.auth-illustration--security`.
- QA note:
  - `php -l` passed for updated auth Blade files and auth stylesheet.
  - `php artisan view:cache` passed after UI refresh.

Date: 2026-04-13
Completed in this cycle:

- Auth branding & forgot-password UX modernization:
  - upgraded `auth.forgot-password` and `auth.reset-password` views to modern split-layout UI aligned with custom auth theme.
  - refreshed `auth.login` UI structure to use the same branding primitives and auth theme variables for consistency.
  - all auth branding data now comes from `company_settings` via `CompanyBrandComposer`: company name, tagline, footer note, logo, favicon, and auth theme colors.
- Database-driven auth theme support:
  - added migration `2026_04_13_110000_add_auth_theme_fields_to_company_settings_table.php` to store auth-specific colors:
    - `auth_primary_color`
    - `auth_primary_hover_color`
    - `auth_background_from_color`
    - `auth_background_to_color`
    - `auth_card_background_color`
    - `auth_card_border_color`
  - added validation + normalization in `CompanySettingController` and editable color-picker fields in Company Settings UI.
  - updated `CompanySetting` model fillable fields to include all new auth theme attributes.
- Composer wiring update:
  - `CompanyBrandComposer` is now registered for `auth.login`, `auth.forgot-password`, and `auth.reset-password`.
- QA note:
  - `php -l` passed for updated PHP and Blade files.
  - `php artisan route:list --path=forgot-password -v` confirms route + throttle middleware unchanged and active.
  - `php artisan view:cache` passed after auth view and styling updates.

Date: 2026-04-13
Completed in this cycle:

- Password reset UX and security hardening:
  - enabled visible `Forgot Password?` entry point on custom login page (`resources/views/auth/login.blade.php`) so users can start reset flow directly.
  - added throttling middleware (`throttle:6,1`) to `POST forgot-password` and `POST reset-password` endpoints in `routes/auth.php`.
  - hardened password reset link response in `PasswordResetLinkController` to prevent user-email enumeration by returning a generic success message for registered/unregistered emails.
  - retained explicit throttling feedback (`Please wait before retrying.`) when request rate limit is hit.
- QA note:
  - `php -l` passed for `app/Http/Controllers/Auth/PasswordResetLinkController.php`, `routes/auth.php`, `resources/views/auth/login.blade.php`.
  - `php artisan route:list --path=forgot-password -v` confirms `ThrottleRequests:6,1` middleware on `password.email`.
  - `php artisan route:list --path=reset-password -v` confirms `ThrottleRequests:6,1` middleware on `password.store`.

Date: 2026-04-10
Completed in this cycle:

- Quotation final status lifecycle enhancement:
  - added manual `Set Final` action (`quotations.set-final`) restricted to quotation creator.
  - `Set Final` only allowed when current quotation status is `approved`.
  - added auto-finalization for `approved` quotations when `validity_date` is before current date.
  - non-`approved` statuses are intentionally left unchanged when expired.
- Quotation UI behavior alignment:
  - quotation show/edit validation panel now shows creator `Set Final` action in approved state.
  - quotation PDF action now available for both `approved` and `final` statuses.
- Quotation ownership hardening:
  - quotation data mutation access (`edit`, `update`, `deactivate/activate`, `global discount`) is now restricted to quotation creator only.
  - manager/director can no longer edit quotation data created by other users.
- Approved quotation edit behavior adjustment:
  - quotation with `approved` status can still be edited by its creator (blocked only when status is `final`).
  - after creator updates an `approved` quotation, status is auto-reset to `pending` and approval metadata is cleared for re-approval flow.
- Quotation edit itinerary selector fix:
  - linked itinerary now remains visible in edit form selector even when itinerary status is `final` or `is_active = false`.
  - prevents false empty-state message ("Belum ada itinerary aktif yang siap dipakai untuk quotation.") on linked quotations.
- Quotation listing scope split:
  - `quotations.index` now focuses on published outcomes only (`approved` and `final`).
  - added dedicated `quotations.my` page so each user can manage all quotations they created across statuses.
  - quotation export now supports scope-aware behavior (`published` and `my`) to match visible list context.
- Sidebar navigation update:
  - added `My Quotations` entry under Reservations for direct access to creator-owned quotation management.
- Itinerary duration guard hardening:
  - create/edit itinerary now enforces duration limits: `duration_days` min 1 max 7, `duration_nights` min 0 max 6.
  - itinerary form inputs now include corresponding HTML min/max constraints.
  - client-side duration sync now clamps values to rule limits to prevent out-of-range day generation.
- Cross-module consistency guard:
  - itinerary lifecycle sync now treats quotation status `final` as final-equivalent to avoid status downgrade.
- QA note:
  - `php -l` passed for `app/Http/Controllers/Sales/QuotationController.php`, `app/Models/Itinerary.php`, `routes/web.php`.
  - `php artisan test tests/Feature/Modules/QuotationsGlobalDiscountRoleTest.php` passed (19 tests).
  - `php artisan view:cache` passed after Blade updates.

Date: 2026-04-10
Completed in this cycle:

- Profile page safety UI adjustment:
  - removed `Delete Account` panel include from `resources/views/profile/edit.blade.php`.
  - profile update and password update panels remain unchanged.
  - `profile.destroy` route/controller were intentionally left intact to avoid auth-flow regression.
- Tourist Attraction gallery image durability hardening:
  - improved `ImageThumbnailGenerator::processAndGenerate()` fail-safe so original upload is retained when processed-file write is not persisted.
  - hardened `TouristAttractionController::storeGalleryImages()` to persist only gallery paths that actually exist on `public` disk (prevent DB path drift to missing files).
  - update flow now auto-cleans stale tourist-attraction gallery references whose files are already missing on disk.
  - updated tourist-attraction edit gallery preview rendering to check storage existence before emitting image URL, reducing broken-image 404 loops in UI.
- Top navigation currency selector alignment update:
  - added dedicated class `nav-currency-select` to center selected currency text in the top-nav switch.
  - switched to custom caret icon overlay to keep visual centering stable across browsers.
- QA note:
  - verified profile page still renders Update Profile + Update Password forms.
  - `php -l` passed for `app/Support/ImageThumbnailGenerator.php` and `app/Http/Controllers/Admin/TouristAttractionController.php`.
  - `php artisan view:cache` passed after Blade update.

Date: 2026-04-09
Completed in this cycle:

- Performed full markdown documentation audit and deduplication across repository root.
- Consolidated overlapping docs into a clearer hierarchy:
  - `PROJECT_KNOWLEDGE_BASE.md` remains source of truth,
  - `PROJECT_GUIDELINES.md` focused to execution protocol and governance,
  - `README.md` simplified to quick start + doc map.
- Added consolidated technical fix reference:
  - new `docs/technical/TECHNICAL_FIX_NOTES.md` for merged fix notes.
- Added archive pointer for older duplicated snapshots:
  - new `docs/archive/PROJECT_AUDIT_ARCHIVE.md`,
  - `ANALYSIS_REPORT.md` and `QUICK_SUMMARY.md` converted to archived pointers.
- Converted `ACTIVITY_LOG_FIX.md`, `SIDEBAR_COLLAPSE_FIX.md`, and `modul.md` into lightweight pointer docs to prevent duplicate maintenance.
- Synced `PROJECT_KNOWLEDGE_BASE.md` version/date and document map section with new consolidated structure.
- Performed phase-2 consolidation on technical docs:
  - rewrote `ITINERARY_CREATE_EDIT_FLOW.md` to remove repeated/legacy notes and keep focused create/edit flow guidance,
  - streamlined `LAYOUT_GUIDE.md` into baseline layout patterns only,
  - reduced duplication in `PROJECT_KNOWLEDGE_BASE.md` section 8 by converting deep implementation notes into concise summaries + pointers.
- Performed phase-3 documentation structure migration:
  - created `docs/` hierarchy (`core`, `technical`, `archive`, `changelog`) with central map at `docs/README.md`,
  - moved canonical technical docs to `docs/technical/*` and layout guide to `docs/core/LAYOUT_GUIDE.md`,
  - moved archive references to `docs/archive/*` and historical roadmap entries to `docs/changelog/ROADMAP_CHANGELOG_ARCHIVE.md`,
  - retained legacy root filenames as compatibility pointers.
- Performed phase-4 changelog archive normalization:
  - standardized archive entries into heading-based sections with a clear entry index,
  - preserved original historical content while improving scan/read speed,
  - added archive note clarifying historical path wording may reflect pre-migration locations.
- Activity Timeline pagination enhancement (no full page reload):
  - implemented AJAX pagination handler in `resources/views/components/activity-timeline.blade.php`,
  - timeline pagination links are now rendered inside the component and intercepted via fetch + partial panel replacement,
  - optimized to fragment mode: Inquiry/Itinerary/Quotation show+edit now return timeline panel HTML only when header `X-Activity-Timeline-Ajax: 1` is present (`X-Activity-Timeline-Fragment: 1` response),
  - applied across Inquiry/Itinerary/Quotation show+edit pages by removing external timeline paginator rendering.
- QA note:
  - verified all root `.md` files are still present and readable,
  - verified canonical docs in `docs/` are readable and pointer docs resolve correctly,
  - `php -l` passed for updated controllers (`ItineraryController`, `InquiryController`, `QuotationController`),
  - `php artisan view:cache` passed after activity timeline pagination update.

Date: 2026-04-16
Completed in this cycle:

- Role & Permission hardening (permission-first access control):
  - super-admin privacy hardening:
    - `Super Admin` role/user is hidden from non-superadmin role-management surfaces.
    - non-superadmin cannot access/edit/delete `Super Admin` user/role even via direct URL.
    - role template cloning for non-superadmin excludes `Super Admin` template.
    - error responses for protected Super Admin targets are normalized to non-disclosing behavior (`404`).
    - UI identity masking applied on non-admin modules: when historical records reference a super-admin user, displayed name is masked as `System` for non-superadmin viewers.
    - non-superadmin dashboard/user counters and creator filters now exclude super-admin user/role data.
    - roles & permissions screen hardening:
      - non-superadmin role search/list now enforces hidden `Super Admin` role even under keyword search.
      - role index KPI cards for non-superadmin are based on superadmin-excluded dataset.
  - super-admin full-access baseline:
    - global Gate `before` allows `Super Admin` all abilities without requiring explicit permission mapping.
  - sidebar filtering now prioritizes permission/module access; static role constraints are enforced only for explicit role-only entries.
  - fixed hidden-menu issue where users (e.g., Director) with full module permissions still could not see some sidebar features due to hardcoded role lists.
  - `module.permission` middleware is now fully CRUD-permission driven; removed hardcoded delete restriction to Super Admin.
  - stage-2 non-CRUD hardening completed for dashboard/quotation flow:
    - dashboard redirect now uses dashboard permissions priority instead of role checks.
    - quotation approval/reject/set-pending/global-discount/validation routes now guarded by specific permissions (`quotations.approve`, `quotations.reject`, `quotations.set_pending`, `quotations.global_discount`, `quotations.validate`).
    - quotation show/edit action visibility now follows permission checks (no role hardcode).
    - quotation approval role resolution and approval bell context now derive from permissions (not role hardcode).
    - quotation validation actor/policy now use `quotations.validate` permission.
  - updated currency rate update authorization to use `module.currencies.update` permission instead of hardcoded role checks.
  - aligned UI action visibility to permission checks on key pages:
    - `currencies` (bulk update + delete actions)
    - `users` (delete action)
    - `roles` list partial (delete action)
  - added regression tests for:
    - sidebar permission-first behavior
    - module delete access by `module.*.delete` permission
- QA note:
  - `php artisan test tests/Feature/Modules/RolePermissionAccessControlTest.php` passed.
  - `php artisan view:cache` passed.

- Itinerary Day Card visual consistency standardization:
  - Day 1 card is now enforced as the visual baseline for all Day N cards in create/edit itinerary.
  - standardized key wrappers and controls (`day card`, point cards, primary add-item button, core input/select controls) for cloned and existing Day sections.
  - removed per-point theme color switching (`theme-airport` / `theme-hotel`) so each Day card stays visually consistent.
- QA note:
  - `php artisan view:cache` passed after Blade/JS updates.

Historical detailed entries moved to:
- docs/changelog/ROADMAP_CHANGELOG_ARCHIVE.md


