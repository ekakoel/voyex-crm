# Voyex CRM - Project Knowledge Base

Version: 1.0  
Date: 2026-03-30  
Status: Consolidated source of truth (derived from all project `.md` docs currently in repository)

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
- Itinerary/Quotation/Booking edit-delete: creator policy with Super Admin override (per guideline direction)
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

---

## 8. Itinerary Create/Edit: Critical Technical Notes

The itinerary form is a complex dynamic DOM-driven editor (`resources/views/modules/itineraries/_form.blade.php` + controller normalization).  
It uses:
- Blade prefill/grouping
- JavaScript runtime recalculation/reindexing
- Backend normalization/sync

Important behavior:
- Payload names for schedule items are built/rebuilt by JS (`reindex()` pattern)
- Day-level state (start/end points, transport, include/exclude, main experience) is managed separately
- Map preview reflects DOM state, not authoritative backend state
- Create/update may auto-transition related inquiry `draft -> processed` (if allowed by status rule)

Known risk area from docs:
- Transport unit submit validation has been noted as potential table/model mismatch and should be verified before related refactors.

### 8.1 Itinerary Detail Map (Show Page) Reference

Map pada halaman detail itinerary memiliki arsitektur khusus dan sudah didokumentasikan terpisah di:
- `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`

Ringkasan aturan penting:
- Marker diambil dari kombinasi start point, item schedule (attraction/activity/F&B), dan end point per day.
- Polyline harus mengikuti route jalan (OSRM), tanpa fallback garis lurus.
- Render harus memakai guard concurrency/stabilitas (render token + abort fetch + busy guard).
- Mode renderer yang dipakai untuk stabilitas adalah SVG (`preferCanvas: false`, `renderer: L.svg()`).
- Tombol `All Days` dan `Day N` wajib memfilter marker + route sesuai day yang dipilih.

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
- Audit trail depth
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

## 12. Source Documents Used for Consolidation

- `README.md`
- `PROJECT_GUIDELINES.md`
- `QUICK_SUMMARY.md`
- `VOYEX_CRM_AI_GUIDELINE.md`
- `VOYEX_CRM_SYSTEM_ROADMAP.md`
- `LAYOUT_GUIDE.md`
- `CHEAT_SHEET.md`
- `ITINERARY_CREATE_EDIT_FLOW.md`
- `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
- `ANALYSIS_REPORT.md`
- `SIDEBAR_COLLAPSE_FIX.md`
- `modul.md`

---

## 13. Maintenance Rule for This File

When project conventions or roadmap status changes, update this file first, then align the more detailed docs as needed.  
If a conflict exists between docs, follow this priority:

1. `PROJECT_GUIDELINES.md`
2. `VOYEX_CRM_SYSTEM_ROADMAP.md`
3. `LAYOUT_GUIDE.md`
4. Other supporting docs
