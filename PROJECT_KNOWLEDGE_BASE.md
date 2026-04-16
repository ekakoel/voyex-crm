# Voyex CRM - Project Knowledge Base

Version: 2.2  
Date: 2026-04-10  
Status: Consolidated source of truth (post markdown audit, deduplication, and docs structure migration)

---

## 1. System Identity

Voyex CRM adalah sistem CRM khusus travel agent untuk mengelola siklus bisnis end-to-end:

`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`

Output bisnis utama:
- Itinerary
- Quotation
- Invoice

Prinsip utama:
- Business-flow oriented
- Modular and scalable
- Maintainable and performance-aware

---

## 2. Core Stack

Current implementation direction in project docs:
- Backend: Laravel 10.x
- PHP: 8.2.x
- Database: MySQL
- Auth/RBAC: Laravel Breeze + Spatie Permission
- Frontend/UI pattern: Blade + Tailwind utility + shared design system classes (`app-card`, `app-table`, `btn-*`, `app-input`)
- Build tool: Vite
- Export: PDF (DomPDF), CSV

Note on documentation conflict:
- Some older docs mention "Blade + Bootstrap".
- Newer operational/project-guideline docs and UI conventions indicate Tailwind-based component/class standardization is the active direction.

---

## 3. Business Flow and Status Rules

### 3.1 Main Flow
1. Customer data captured/managed
2. Inquiry created and assigned
3. Follow-up and communication tracked
4. Itinerary structured per day
5. Quotation generated and reviewed
6. Booking created from quotation
7. Invoice generated and tracked

### 3.2 Standard Statuses (critical)
Applies to: Inquiries, Itineraries, Quotations, Bookings, Invoices
- `draft`
- `processed`
- `pending`
- `approved`
- `rejected`
- `final`

Rule:
- If status is `final`, record is view-only (no edit/delete/mutation actions).

Additional quotation rule:
- Quotation can move to `final` only from `approved` status.
- Transition to `final` happens when creator explicitly sets it, or when `validity_date` has passed.
- Expired quotations with non-`approved` status must keep their existing status.
- Quotation with status `approved` can still be edited by its creator; after data update the status is reset to `pending` for re-approval.

---

## 4. Modules and Functional Scope

### 4.1 Master Data Modules
- Destinations
- Vendor Management
- Activities
- Food & Beverages
- Accommodations
- Hotels
- Airports
- Transports
- Tourist Attractions
- Currencies
- User Manager
- Role Manager
- Module Management

### 4.2 Transaction Modules
- Customers
- Inquiries
- Itineraries
- Quotations
- Bookings
- Invoices

### 4.3 Non-module Feature Areas
- Role-based dashboards (Super Admin / Admin / Manager / Marketing / Reservation / Director / Finance / Editor)
- Access Matrix
- Company Settings

---

## 5. Roles, Permissions, and Access Guards

Primary roles:
- Super Admin
- Administrator
- Director
- Manager
- Reservation
- Accountant / Finance
- Marketing
- Editor

Access controls in use:
- `auth`
- `role:*`
- `permission:*`
- `module:*`
- `module.permission:{moduleKey}`

Key policy/business guard examples:
- Inquiry update: creator or assigned user
- Itinerary/Booking edit-delete: creator policy with Super Admin override (per guideline direction)
- Quotation data mutation (edit/delete/global discount): creator-only before status `final`
- Discount/promo approval path: Manager/Director
- Follow-up mark done: creator inquiry or assigned user

---

## 6. Data and Domain Relationships (high-level)

Core relationships:
- User -> hasMany Customers (`created_by`)
- User -> hasMany Inquiries (`assigned_to`)
- Customer -> hasMany Inquiries
- Inquiry -> hasOne Quotation
- Quotation -> hasMany QuotationItems, hasOne Booking
- QuotationItem -> belongsTo Service
- Service -> belongsTo Vendor

Operational domain relationships:
- Transport -> belongsTo Vendor (provider)
- Accommodation -> hasOne Hotel (domain expansion path)
- Hotel -> room/image/type/price/promo/package/extra-bed style sub-data
- Inquiry -> followups + communications
- Itinerary -> day-based points/items/transport units and linked services

---

## 7. UI and Layout Standards (current baseline)

### 7.1 Page Layout
- Use master layout sections: `page_title`, `page_subtitle`, `page_actions`
- Standard index layout: left filter card, right result table/content
- Standard detail/create/edit layout: main content + right supporting panel

### 7.2 Component Standards
- Table container: `app-card`
- Tables: `app-table`
- Inputs/selects: `app-input` (except textarea as needed)
- Buttons: standardized `btn-*` variants (`btn-primary`, `btn-secondary`, `btn-outline`, `btn-ghost`, and `-sm` variants)

### 7.3 Index Standards
- Summary stats under header (`app-card-grid`, and `<x-index-stats>` where implemented)
- Filter forms use consistent grid and `per_page` options (10/25/50/100)
- Query persistence via `withQueryString()`
- Mobile card list companion for table views

### 7.4 Data Lifecycle UX Rule
- Deletion for many modules replaced by soft-delete lifecycle:
  - `Deactivate` / `Activate`
  - Active/Inactive status visible in index

### 7.5 AJAX Index Filter Standard (Project Standard)
Untuk semua halaman index module yang memakai panel filter di sisi kiri, standar default adalah AJAX auto-filter (tanpa tombol `Filter`).

Struktur minimal yang wajib dipakai:
- Root page: `data-service-filter-page`
- Form filter: `data-service-filter-form`
- Input filter yang memicu auto refresh: `data-service-filter-input`
- Reset link: `data-service-filter-reset`
- Container hasil (table/cards/pagination): `data-service-filter-results`

Perilaku standar:
- Input text: auto request dengan debounce (tanpa submit manual)
- Select / change input: auto request langsung
- Pagination link: di-handle AJAX juga
- URL query tetap ter-update (`pushState/replaceState`) agar bisa di-refresh/share/back-forward
- Tombol `Reset` tetap tersedia

Catatan implementasi:
- Response tetap berupa HTML halaman index yang sama; frontend akan mengambil ulang node `data-service-filter-results`.
- Gunakan `withQueryString()` pada paginator.
- Opsi `per_page` tetap wajib tersedia (umum: `10/25/50/100`).

---

## 8. Itinerary Create/Edit: Critical Technical Notes

Itinerary create/edit adalah area berisiko tinggi karena menggabungkan:
- Blade prefill state,
- JavaScript reindex/recalc dinamis,
- backend normalization sebelum sync relasi.

Ringkasan aturan:
- payload schedule final dibangun ulang via `reindex()` sebelum submit,
- day-level point/transport/include-exclude disimpan pada layer payload terpisah,
- inquiry terkait dapat auto-transisi `draft -> processed` sesuai rule status.
- duration guard itinerary dibatasi ketat:
  - `duration_days`: minimum 1, maksimum 7,
  - `duration_nights`: minimum 0, maksimum 6 (dan tetap <= `duration_days`).

Referensi detail implementasi:
- `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`

### 8.1 Itinerary Detail Map (Show Page) Reference

Map show-page itinerary dipisah dari map preview create/edit.
Aturan utama:
- marker dari point tersimpan per day,
- polyline route jalan berbasis OSRM (tanpa fallback garis lurus),
- render guard wajib dipertahankan untuk hindari race condition.

Referensi detail:
- `docs/technical/ITINERARY_DETAIL_MAP_ARCHITECTURE.md`

### 8.2 Quotation Create/Edit: Critical Notes

Area quotation create/edit tetap kritis pada:
- generated item dari itinerary + editable override user,
- kalkulasi discount dua lapis (row level + header level),
- approval workflow berbasis role.

Tambahan workflow status quotation:
- action manual `Set Final` hanya untuk creator quotation dan hanya saat status `approved`.
- auto-finalization untuk quotation `approved` berjalan saat `validity_date` sudah lewat.
- creator dapat mengubah data quotation berstatus `approved`; setiap perubahan akan reset status menjadi `pending`.
- listing quotation dipisah per konteks:
  - `Quotations` page menampilkan hasil publish (`approved`/`final`) untuk monitoring outcome.
  - `My Quotations` page menampilkan seluruh quotation yang pernah dibuat user login untuk kebutuhan pengelolaan data.
- behavior ini diselaraskan dengan lifecycle itinerary agar status akhir tetap konsisten lintas modul.

Referensi verifikasi approval:
- `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`

---

## 9. Engineering and Architecture Principles

Every change should preserve:
- Main sales process integrity
- Clean data relationships (avoid unnecessary duplication)
- Performance (eager loading, pagination, indexing, avoid N+1)
- Security (RBAC, validation, sanitization, auditability)
- Long-term direction readiness:
  - Multi-tenant SaaS
  - Payment integration
  - Automation (email/WA/reminders)
  - Reporting/BI

---

## 10. Current Delivery Status (roadmap snapshot)

Broadly implemented:
- Core auth, role/permission, module toggles
- Customer, inquiry, quotation, booking, invoice core workflows
- Major master-data CRUD modules
- Itinerary basic multi-day structure + PDF support
- Soft delete/deactivate lifecycle across many modules
- Standardized index UI rollout across many views

Partially implemented:
- Audit trail depth (activity logging now implemented for core models, needs advanced dashboard)
- Revenue/analytics depth
- Hotels feature depth (ongoing expansion)
- Payment tracking depth

Not yet fully implemented (critical backlog):
1. Margin and profit calculation
2. Expense to profit linking
3. Participant management in booking operations
4. Auto reminder engine
5. Itinerary template/versioning
6. Advanced reporting/analytics

---

## 11. QA and Change Discipline

After each change, minimum QA expectation:
1. Visual/layout sanity on touched pages
2. Core actions on touched pages (create/edit/list/show)
3. Empty/error states where relevant
4. No JS console errors on interactive pages
5. Brief QA notes in delivery summary

---

## 12. Documentation Map (Current)

Core references:
- `README.md`
- `PROJECT_GUIDELINES.md`
- `VOYEX_CRM_SYSTEM_ROADMAP.md`
- `docs/core/LAYOUT_GUIDE.md`
- `VOYEX_CRM_AI_GUIDELINE.md`
- `docs/README.md`

Technical references:
- `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`
- `docs/technical/ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
- `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`
- `docs/technical/TECHNICAL_FIX_NOTES.md`

Archive docs:
- `docs/archive/PROJECT_AUDIT_ARCHIVE.md`
- `docs/changelog/ROADMAP_CHANGELOG_ARCHIVE.md`

---

## 12a. Audit Trail & Activity Logging System

### Models with Activity Logging
Models using `LogsActivity` trait now automatically record all create/update/delete operations:
- `Itinerary`
- `Activity`
- `TouristAttraction`
- `FoodBeverage`

### How It Works
1. **Trait Implementation:** `App\Traits\LogsActivity`
   - Auto-hooks into Eloquent created/updated/deleted events
   - Writes to `activity_logs` table with full change tracking
   
2. **Data Structure:**
   - `user_id`: who made the change
   - `module`: model class name
   - `action`: 'created'|'updated'|'deleted'|'reminder_added'|etc
   - `subject_id`: record ID
   - `subject_type`: MorphMap alias (Itinerary, Activity, TouristAttraction, FoodBeverage)
   - `properties`: json array with change details (field names, before/after values)

3. **UI Display:**
   - Component: `<x-activity-timeline :activities="$activities" />`
   - Shows in Itinerary detail page with full change history
   - Renders change details: "Field: oldValue → newValue"

### Related Code Files
- `app/Traits/LogsActivity.php` - auto-logging trait
- `app/Services/ActivityAuditLogger.php` - detailed audit service
- `app/Models/ActivityLog.php` - activity log model
- `resources/views/components/activity-timeline.blade.php` - timeline UI

### Status (as of 2026-04-06)
- **DONE:** Auto activity logging for Itinerary, Activity, TouristAttraction, FoodBeverage
- **DONE:** Activity Timeline UI display
- **PARTIAL:** Activity Audit Logger service (exists but not fully utilized in all update flows)
- **TODO:** Advanced audit dashboard with filtering

---

## 13. Maintenance Rule for This File

When project conventions or roadmap status changes, update this file first, then align the more detailed docs as needed.  
If a conflict exists between docs, follow this priority:

1. `PROJECT_GUIDELINES.md`
2. `PROJECT_KNOWLEDGE_BASE.md`
3. `VOYEX_CRM_SYSTEM_ROADMAP.md`
4. `docs/core/LAYOUT_GUIDE.md`
5. Other supporting docs

Mandatory documentation discipline:
1. Setiap perubahan code wajib dicatat di `VOYEX_CRM_SYSTEM_ROADMAP.md` pada bagian `CHANGELOG (LATEST)`.
2. Setiap perubahan code wajib update minimal satu dokumen `.md` terkait scope perubahan.
3. Jika perubahan menyentuh lintas modul/arsitektur, wajib update file ini (`PROJECT_KNOWLEDGE_BASE.md`) agar source of truth tetap sinkron.
