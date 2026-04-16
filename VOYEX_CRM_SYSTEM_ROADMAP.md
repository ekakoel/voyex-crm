# VOYEX CRM -- SYSTEM ROADMAP

Version: 1.3  
Last Updated: 2026-04-13

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
Convert Quotation â†’ Booking | DONE | Booking creation flow
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

1. Approval Workflow (Quotation) â€” DONE (basic), enhance matrix + audit
2. Margin & Profit Calculation â€” TODO
3. Structured Itinerary Engine â€” DONE (basic), needs pricing + templates
4. Expense â†’ Profit Linking â€” TODO
5. Audit Trail System â€” PARTIAL (activity logs only)
6. Participant Management â€” TODO
7. Auto Reminder Engine â€” TODO

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

Historical detailed entries moved to:
- docs/changelog/ROADMAP_CHANGELOG_ARCHIVE.md
