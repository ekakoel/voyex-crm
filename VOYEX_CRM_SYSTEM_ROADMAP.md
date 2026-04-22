# VOYEX CRM -- SYSTEM ROADMAP

Version: 1.4  
Last Updated: 2026-04-22

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

Date: 2026-04-22
Completed in this cycle:

- Service Map Island Transfer route rollout:
  - extended `ServiceMapController` to include `IslandTransfer` dataset in map payload.
  - added Island Transfer map markers for departure and arrival points:
    - departure marker uses ship icon,
    - arrival marker uses anchor icon.
  - added route payload (`routes`) for map rendering:
    - prioritizes `route_geojson` from `island_transfers`,
    - falls back to departure->arrival line when `route_geojson` is not available.
  - updated Service Map legend and filter with new `Island Transfers` type.
  - updated frontend map engine (`resources/js/service-map.js`) to:
    - parse route payload,
    - render transfer polyline,
    - support filter toggle for both transfer markers and route lines,
    - include visible route segments in auto-fit bounds.
  - impact:
    - users can now see inter-island transfer routes directly on Service Map,
    - Service Map now represents both service points and sea transfer paths in one page.

- Service Map Destination -> Kabupaten overlay rollout (Indonesia):
  - added local kabupaten/kota GeoJSON dataset:
    - `public/data/IDN_adm_2_kabkota.json`.
  - extended Service Map frontend to render regency overlays for Destination points:
    - loads kabupaten GeoJSON from local static asset,
    - matches Destination coordinate to kabupaten polygon (point-in-polygon),
    - draws polygon overlay for matched kabupaten/kota area.
  - overlay visibility is synchronized with `Destinations` legend toggle.
  - map fit-bounds now includes visible destination overlays in addition to markers/routes.
  - impact:
    - destination points are now spatially linked to Indonesian regency boundaries on Service Map,
    - users can see not only point markers, but also the destination administrative area context.

- Service Map Destination -> Province overlay alignment (Indonesia):
  - updated Service Map to use province boundary dataset (`adm1`) for destination administrative overlay:
    - `public/data/IDN_adm_1_province.json`.
  - destination markers now carry explicit `province` payload from backend.
  - destination overlay matching is now province-name based (linked to `destinations.province`) instead of point-in-polygon kabupaten lookup.
  - added province name normalization/alias mapping to bridge naming variants, including:
    - `DKI Jakarta` <-> `Jakarta Raya`,
    - `DI Yogyakarta` <-> `Yogyakarta`,
    - `Kepulauan Bangka Belitung` <-> `Bangka-Belitung`,
    - Papua split names mapped to available ADM1 boundaries in dataset.
  - destination overlay remains synchronized with `Destinations` legend toggle and map fit-bounds.
  - impact:
    - destination to province linkage now follows canonical destination province data model,
    - province overlays on Service Map are consistent with destination master data selection.

- Destination detail Island Transfer + province map rollout:
  - added destination-to-island-transfer linkage on destination detail page via vendor destination relation
    (with province fallback for legacy vendor rows).
  - destination detail now shows linked Island Transfer list with quick route/context info and status badge.
  - added sidebar province map card on destination detail:
    - loads province boundary from `public/data/IDN_adm_1_province.json`,
    - highlights matched province polygon,
    - places destination marker on map (or province center fallback when coordinate is missing).
  - impact:
    - destination detail now gives direct operational visibility to related island transfer services,
    - province context is visible in-page without opening Service Map separately.
- QA note:
  - `php -l app/Http/Controllers/Admin/ServiceMapController.php` passed.
  - `php artisan view:cache` passed.
  - `npm.cmd run build` passed.

Date: 2026-04-21
Completed in this cycle:

- Artisan bootstrap memory exhaustion fix:
  - fixed console command registration recursion in `app/Console/Kernel.php`.
  - removed recursive `$this->commands([...])` call from inside `Kernel::commands()` that caused infinite command registration loop during Artisan bootstrap.
  - retained standard command loading via `$this->load(__DIR__.'/Commands')` and `routes/console.php`.
  - cleaned the stale manual registration path for missing `WarmInquiryCache` command.
  - impact:
    - `php artisan optimize:clear` and other Artisan commands can bootstrap normally again,
    - prevents memory exhaustion during command discovery.
- QA note:
  - bootstrap isolation confirmed the failure was inside `Kernel::commands()`.
  - `php artisan optimize:clear` passed after the fix.

- Shared performance optimization baseline:
  - added `App\Support\SchemaInspector` for per-request memoization of repeated schema checks.
  - added `App\Support\CompanySettingsCache` and migrated auth/sidebar branding reads to shared cached data.
  - optimized `App\Services\ModuleService` with cached enabled-map/list reads and explicit `flushCache()` invalidation after module toggle.
  - optimized `App\Support\Currency` with cached currency metadata/options and explicit invalidation after currency mutations.
  - optimized `SidebarComposer`:
    - uses module enabled map instead of per-item module DB checks,
    - uses cached company settings and currency options,
    - caches quotation approval bell count briefly per user/role.
  - optimized `IndexStatsComposer` with short-lived stats card cache for module index pages.
  - optimized Super Admin dashboard aggregate payload and trend endpoint with short-lived cache.
  - added canonical performance standard documentation:
    - `docs/technical/PERFORMANCE_OPTIMIZATION_STANDARD.md`.
  - impact:
    - lower repeated DB/query overhead on shared layout requests,
    - faster sidebar/layout render path,
    - faster heavy dashboard response while retaining near-real-time data windows,
    - clearer cache invalidation standard for future feature work.
- QA note:
  - `php -l` passed for updated support classes, composers, services, and controllers.
  - `php artisan optimize:clear` passed.
  - `php artisan view:cache` passed.
  - `php artisan route:list` passed.

- Master layout mobile top spacing adjustment:
  - added `pt-6` (`1.5rem`) to `main.app-content` so the page header has breathing room below the sticky top navigation on mobile.
  - removed the temporary Super Admin dashboard content-grid top padding to avoid double spacing below the header.
  - impact:
    - page headers no longer sit flush against the sticky top navigation on mobile devices,
    - dashboard content spacing remains governed by the global page header/content structure.
- QA note:
  - `php artisan view:cache` passed.

- Global card spacing standardization:
  - added global `--app-card-gap: 0.75rem` (`gap-3`) in `resources/css/app.css`.
  - standardized repeated card/grid/stack containers to use the same card rhythm:
    - module grids,
    - module side/main columns,
    - index stat grids,
    - dashboard KPI grids,
    - responsive card lists,
    - direct app/superadmin card grids.
  - hardened the global spacing guard for legacy wrappers that contain cards:
    - grid/flex parents with direct card children are normalized to `gap-3`,
    - `space-y-4/5/6/8` card stacks are normalized to the global card gap,
    - direct sibling cards now keep `0.75rem` vertical rhythm even when older pages use local margin utilities.
  - added reusable `.app-card-stack` and `.app-card-row` helpers for future pages.
  - updated `docs/core/LAYOUT_GUIDE.md` to define `gap-3` as the global card/container spacing baseline and document the new helper classes.
  - impact:
    - card spacing is more compact and consistent across project pages,
    - future pages have a clear spacing standard instead of local `gap-5/gap-6/space-y-6` drift.
- QA note:
  - `php artisan view:cache` passed.
  - `npm.cmd run build` passed after CSS standardization.

- Food & Beverage pricing currency canonicalization (IDR persistence):
  - fixed create/edit F&B pricing persistence so `contract_rate` and fixed `markup` are always stored in IDR regardless of active display currency.
  - backend normalization now converts submitted display-currency values to IDR using current currency rate before publish-rate computation and persistence.
  - updated F&B form initial values (create/edit/copy) to render pricing fields in active display currency, then safely round-trip to IDR on submit.
  - percent markup behavior is preserved as percentage (not currency-converted), while fixed markup is converted to IDR.
  - impact:
    - switching top-nav currency (e.g., USD) no longer causes raw foreign-currency values to be persisted as if they were IDR,
    - F&B price data remains consistent with project-wide IDR canonical storage rule.
- QA note:
  - `php -l app/Http/Controllers/Admin/FoodBeverageController.php` passed.
  - `php artisan view:cache` passed.

- Cross-module pricing canonicalization rollout (IDR persistence baseline):
  - added reusable controller concern `NormalizesDisplayCurrencyToIdr` to standardize server-side display-currency -> IDR normalization.
  - applied canonical IDR persistence on create/edit flows for pricing modules:
    - Activities (`adult_contract_rate`, `child_contract_rate`, fixed `adult_markup`, fixed `child_markup`),
    - Transports (`contract_rate`, fixed `markup`, `overtime_rate`),
    - Tourist Attractions (`contract_rate_per_pax`, fixed `markup`),
    - Island Transfers (`contract_rate`, fixed `markup`),
    - Hotels seasonal prices (`contract_rate`, fixed `markup`, `kick_back`),
    - Food & Beverage (existing fix retained and refactored to shared concern).
  - synchronized form prefill values on edit/create partials to render stored IDR amounts in active display currency before submit:
    - `activities/_form`, `transports/_form`, `tourist-attractions/_form`, `island-transfers/_form`, `hotels/partials/_prices`, `food-beverages/_form`.
  - percent markup fields remain percentage-only and are not currency-converted.
  - impact:
    - all core service-pricing modules now persist monetary values in IDR consistently,
    - avoids double-conversion risk when editing existing records under non-IDR display currency.
- QA note:
  - `php -l` passed on updated controllers and shared concern.
  - `php artisan view:cache` passed after Blade updates.

- F&B meal period input upgrade (checkbox multi-session):
  - changed F&B create/edit `Meal Period` from free-text input to checkbox multi-select:
    - `Breakfast`,
    - `Lunch`,
    - `Dinner`.
  - users can now select one, two, or all session options per package.
  - backend now accepts `meal_periods[]` and normalizes selection into canonical ordered storage text in `meal_period` (`Breakfast, Lunch, Dinner` format as applicable).
  - backward compatibility retained:
    - legacy payload `meal_period` string is still accepted and normalized into selection set.
  - impact:
    - avoids inconsistent manual meal-period typing,
    - improves data consistency for itinerary/F&B session usage.
- QA note:
  - `php -l app/Http/Controllers/Admin/FoodBeverageController.php` passed.
  - `php -l resources/views/modules/food-beverages/_form.blade.php` passed.
  - `php artisan view:cache` passed.

Date: 2026-04-20
Completed in this cycle:

- Date/time format hardening + CI guard:
  - added canonical formatter `\App\Support\DateTimeDisplay` usage across UI/PDF touchpoints for consistent display format.
  - standardized frontend datetime renderers to deterministic output `YYYY-MM-DD (HH:ii)` (non-locale-dependent) for:
    - global local-time renderer in layout,
    - quotation validation dynamic row updates,
    - itinerary create/edit inquiry preview.
  - added GitHub Actions workflow `.github/workflows/date-format-guard.yml`.
  - added guard script `scripts/ci/check-date-format.sh` to block non-standard patterns in PRs:
    - `diffForHumans()` in UI output,
    - non-standard PHP date format usage like `d M Y`, `M Y`, `l, j F Y`,
    - locale-dependent JS datetime rendering (`toLocaleString`, `toLocaleDateString`, `Intl.DateTimeFormat(undefined, ...)`).
  - updated project docs (`README.md`, `PROJECT_GUIDELINES.md`, `PROJECT_KNOWLEDGE_BASE.md`) with date/time standard and CI enforcement.
  - impact:
    - prevents future regressions on date/time display format,
    - guarantees consistent date rendering in web UI and PDF outputs,
    - enforces format compliance automatically on pull requests.

- Island Transfer documentation sync for cross-module stability:
  - completed full markdown review and aligned canonical docs for Island Transfer integration across itinerary, quotation validation, and PDF behavior.
  - updated `docs/technical/ISLAND_TRANSFER_MODULE.md` with:
    - quotation integration flow,
    - canonical `serviceable_type` and `itinerary_item_type` values,
    - validation + master-rate sync scope,
    - PDF itinerary/quotation consistency notes.
  - updated `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`:
    - added explicit schedule payload key `itinerary_island_transfer_items`,
    - clarified map preview rule for transfer segment (`route_geojson` priority, OSRM fallback).
  - updated `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md` and `PROJECT_KNOWLEDGE_BASE.md`:
    - validation-required item list now explicitly includes `Island Transfer`.
  - updated `docs/technical/TECHNICAL_FIX_NOTES.md`:
    - recorded root cause and fix notes for quotation save errors related to invalid `serviceable_type` / `itinerary_item_type` after Island Transfer rollout.
- impact:
  - documentation source-of-truth is now consistent with current code behavior for Island Transfer in create/edit itinerary, quotation validation, and PDF outputs.
  - lowers risk of future regression caused by enum mismatch across quotation pipelines.

- Quotation validation realtime UX + performance optimization:
  - fixed stale validation progress after per-item AJAX save by ensuring progress is always calculated from latest DB state.
  - `Validate Quotation` button now appears immediately once progress reaches 100% (no longer requires `Save Progress` click).
  - reduced redundant validation sync passes in `QuotationValidationService`:
    - `saveItem` now syncs requirement for current item only,
    - `saveProgress`, `validateSelected`, and `finalize` avoid duplicate progress refresh,
    - `syncValidationRequirementsAndMasterRates` refreshes progress once at end of pipeline.
  - replaced in-memory validation progress counters with direct DB aggregate queries (`count/exists`) to reduce memory and response time on large quotation items.
  - removed heavy master-rate sync from non-validation view paths:
    - `quotations.show`,
    - `quotations.edit`,
    - quotation `approve` pre-check.
    These paths now use requirement sync only.
  - impact:
    - faster response on validation-heavy quotations,
    - clearer realtime behavior for validator users on finalization step,
    - reduced latency after clicking `Validate Quotation` before detail page is shown.

- Quotation validation rate period behavior alignment:
  - adjusted validation rate sync so rate records follow validity period upsert rules:
    - if an active rate period exists (`start_date <= today <= end_date`), update existing rate record,
    - if no active period exists (or existing period expired), create a new rate record.
  - applied to:
    - `service_rate_histories` entries for Activity, Food & Beverage, Island Transfer, Transport Unit, Tourist Attraction, and Hotel Room,
    - `hotel_prices` entries for hotel room rate source update.
  - impact:
    - avoids unnecessary duplicate period records,
    - keeps rate history aligned with active validity windows.

- Quotation finalize flow simplification:
  - optimized `Validate Quotation` step to run in lightweight mode:
    - no full item re-sync loop during finalize,
    - finalize now validates completion from DB progress and commits `validation_status` directly.
  - impact:
    - faster finalize response after all items have been validated,
    - reduced redundant workload on quotation with large validation item counts.

- Quotation validation finalize UX feedback:
  - added full-page loading overlay on `Validate Quotation` submit (finalize form).
  - finalize flow remains non-AJAX; AJAX is kept only for per-item `Validate`.
  - impact:
    - clearer submit feedback during reload/redirect phase,
    - reduced perceived slowness and duplicate-click behavior.
  - adjustment:
    - removed button-level spinner on `Validate Quotation`,
    - finalize now behaves as regular form submit and uses the global page spinner only (no page-local overlay) to avoid double-overlay stacking.

- Quotation detail validation transparency enhancement:
  - `Validation Progress` card on quotation detail now shows validator users involved in validation (supports multi-validator scenario).
  - includes per-validator validated item count and latest validation timestamp.
  - impact:
    - clearer audit visibility for collaborative validation workflow,
    - easier reviewer tracking without opening validation page.

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

- Reservation dashboard data consistency hardening:
  - removed legacy booking status dependency (`confirmed`) and aligned dashboard booking queries to active booking lifecycle statuses (`processed`, `approved`, `final`).
  - fixed KPI data wiring for `Quotations Ready to Book` and `Upcoming Trips` by populating `kpis.ready_to_book` and `kpis.upcoming_trips` from controller.
  - tightened `Upcoming Trips` scope to the next 30 days so query behavior matches panel label.

- Seeder baseline consolidation for safer deploy:
  - added single-entry baseline seeder `ProjectBaselineSeeder` and wired `DatabaseSeeder` to call it.
  - retained `PermissionBaselineSeeder` for permission-only sync use case.
  - added deploy shortcut commands in `composer.json`:
    - `composer run db:baseline`
    - `composer run db:deploy-safe`
  - updated `README.md` with deploy baseline workflow.
  - impact: seed execution is simpler and less error-prone across environments.

- Inquiry index itinerary visibility enhancement:
  - Inquiry index now loads itinerary `status` in list eager-load.
  - itinerary label in Inquiry index updated to `Nama Itinerary (Status)` for desktop and mobile cards.
  - impact: users can see itinerary lifecycle state directly from Inquiry list without opening detail page.

- Inquiry assignee access parity with creator:
  - updated Inquiry ownership rule for mutation to `creator OR assigned user` (still permission-gated by `module.inquiries.update`).
  - inquiry communication and follow-up mutation checks now follow the same policy path.
  - impact: assigned PIC can edit and maintain inquiry communication/follow-up flow without requiring creator identity.

- Itinerary PDF item column cleanup:
  - removed item-level `description` rendering from `resources/views/pdf/itinerary.blade.php` in the `Item` column.
  - impact: PDF itinerary item rows are more concise and no longer show long description blocks under item names.

- Quotation-with-itinerary PDF consistency cleanup:
  - removed item-level `description` rendering from `resources/views/pdf/quotation_with_itinerary.blade.php` in the itinerary schedule `Item` column.
  - impact: itinerary schedule table in quotation PDF now matches itinerary PDF behavior (no item description block).

- Quotation validation button loading-state fix:
  - improved per-item validation button state handling on `resources/views/modules/quotations/validate.blade.php`.
  - when `Validate` is clicked, button label is hidden and spinner is shown consistently (including duplicated mobile/desktop action buttons for the same item).
  - added loading spinner state for `Save Progress` and `Validate Quotation` action buttons during submit.
  - impact: clearer submission feedback and reduced double-click risk during async validation save.

- Itinerary day point optionality update:
  - create/edit itinerary now allows `Day N Start Point` and `Day N End Point` to be left empty.
  - removed client-side blocking rule that previously required end point per day.
  - backend day-point normalization and validation now accept empty point type values and persist them as `null`.
  - impact: users can save itinerary schedule flow without forcing start/end point selection on every day.

- Itinerary create/edit quality hardening (error + performance):
  - fixed malformed HTML attribute in day start point options (`data-longitude` typo) that could break DOM dataset parsing.
  - removed duplicate hotel-room mapping query in `normalizeDayPoints()` to reduce unnecessary DB work on itinerary create/update submit.
  - validated Blade compile with `php artisan view:cache`.
  - impact: safer front-end behavior and faster backend normalization path for itinerary save.
  - refined ready-to-book quotation source to approved quotations without booking linkage (`whereDoesntHave('booking')`).
  - aligned recent bookings panel data with active booking statuses and removed unused `statusSummary` payload from reservation dashboard controller.
  - QA note:
    - `php -l app/Http/Controllers/Reservation/DashboardController.php` passed.
    - `php -l resources/views/reservation/dashboard.blade.php` passed.

- Test database safety hardening (critical):
  - enforced isolated test DB at `phpunit.xml` level:
    - `DB_CONNECTION=sqlite`
    - `DB_DATABASE=:memory:`
  - added runtime guard in `tests/CreatesApplication.php` to block test execution when testing environment points to a non-test database.
  - added `.env.testing` local safe defaults and updated `.gitignore` to ignore `.env.testing` from VCS.
  - impact:
    - prevents accidental `RefreshDatabase` wipes on primary/local main database during `php artisan test`.
  - QA note:
    - `php -l tests/CreatesApplication.php` passed.

- Test-suite DB risk elimination by removal:
  - removed all feature/module/auth/dashboard tests that used DB-mutating traits (`RefreshDatabase`, `DatabaseTransactions`) or inherited DB transaction test base.
  - removed `tests/Feature/Modules/ModuleSmokeTestCase.php` and all extending smoke/workflow tests to eliminate accidental DB writes/resets from test execution path.
  - current remaining tests are only non-DB placeholder examples (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`).
  - impact:
    - `php artisan test` no longer has project test files that mutate/reset application database.

- Inquiry edit access hardening (creator-only):
  - updated `InquiryPolicy::update` so edit/update inquiry is allowed only when:
    - user has `module.inquiries.update`, and
    - user is the record creator (`created_by` ownership check).
  - effect:
    - assignee/non-creator can no longer edit inquiry data even if they have update permission.
    - Inquiry edit button visibility (already using `@can`) now follows creator-only policy consistently.
  - governance:
    - documented owner-based mutation standard in `PROJECT_GUIDELINES.md` and `PROJECT_KNOWLEDGE_BASE.md` for future modules.
  - QA note:
    - `php -l app/Policies/InquiryPolicy.php` passed.

- Itinerary edit access hardening (creator-only):
  - updated `ItineraryPolicy::update` so edit/update itinerary is allowed only when:
    - user has `module.itineraries.update`, and
    - user is the record creator (`created_by` ownership check).
  - effect:
    - non-creator cannot edit itinerary even when they have itinerary update permission.
    - existing itinerary edit button visibility (using `@can`) now consistently follows creator-only policy.
  - governance:
    - owner-based mutation standard now explicitly mentions both Inquiry and Itinerary in core docs.
  - QA note:
    - `php -l app/Policies/ItineraryPolicy.php` passed.

- Quotation mutation access hardening (creator-only for edit/delete):
  - updated `QuotationPolicy::update` and `QuotationPolicy::delete` so quotation mutation is allowed only when:
    - user has module permission (`module.quotations.update/delete`), and
    - user is the quotation creator (`created_by` ownership check).
  - effect:
    - non-creator cannot edit/deactivate quotation data even with module update/delete permission.
    - quotation action visibility using `@can('update', $quotation)` / mutation guards via policy now consistently follows creator-only rule.
  - scope note:
    - approval workflow actions (`approve/reject/set pending/set final`) remain permission-based by workflow design and are not changed by this policy hardening.
  - governance:
    - owner-based mutation standard now explicitly includes Inquiry, Itinerary, and Quotation in core docs.
  - QA note:
    - `php -l app/Policies/QuotationPolicy.php` passed.

- Booking mutation access hardening (creator-only for edit/delete):
  - updated `BookingPolicy::update` and `BookingPolicy::delete` so booking mutation is allowed only when:
    - user has module permission (`module.bookings.update/delete`), and
    - user is the booking creator (`created_by` ownership check).
  - effect:
    - non-creator cannot edit/delete booking even with booking update/delete permission.
    - booking action visibility using `@can('update', $booking)` / `@can('delete', $booking)` now follows creator-only policy consistently.
  - QA note:
    - `php -l app/Policies/BookingPolicy.php` passed.

- Invoice module access note (current architecture):
  - confirmed Finance Invoice module remains read-only (`index/show`) with no mutation endpoints (`create/store/edit/update/destroy`) in active routes/controller.
  - governance:
    - documented read-only module standard in core guidelines to prevent accidental mutation-surface expansion without policy design.

- Role & Permission form enhancement for admin tools:
  - added dedicated `System Tools Permissions` section on role create/edit form.
  - surfaced and grouped critical non-module permissions:
    - `services.map.view` (`View Service Map`)
    - `superadmin.access_matrix.view` (`View Access Matrix`)
  - behavior:
    - both permissions are now easy to find and toggle directly from Role & Permission edit flow (no longer buried in generic other-permissions list).
  - QA note:
    - `php -l app/Http/Controllers/Admin/RoleController.php` passed.
    - `php -l resources/views/modules/roles/_form.blade.php` passed.
  - UI placement adjustment:
    - `View Service Map` and `View Access Matrix` are now rendered under `Other Permissions` (bottom section) per operator preference.

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

- Reservation dashboard: added short-term caching for heavy booking queries and weekly trend data (Cache TTL 120s) to improve dashboard responsiveness; added feature test `tests/Feature/Dashboard/ReservationDashboardTest.php` to verify permission guard and rendering. (Controller: `app/Http/Controllers/Reservation/DashboardController.php`, View: `resources/views/reservation/dashboard.blade.php`).
