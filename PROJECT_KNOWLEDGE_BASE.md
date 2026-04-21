# Voyex CRM - Project Knowledge Base

Last Updated: 2026-04-21


Version: 2.6  
Date: 2026-04-21  
Status: Source of truth aktif

---

## 1. System Identity

Voyex CRM adalah sistem CRM travel agent end-to-end:

`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`

Prinsip utama:
- process integrity,
- modular scalability,
- permission-first access control,
- auditability.

---

## 2. Core Stack

- Backend: Laravel 10.x
- PHP: 8.2.x
- Database: MySQL
- Auth/RBAC: Laravel Breeze + Spatie Permission
- UI: Blade + Tailwind utility + shared class system (`app-card`, `app-table`, `btn-*`, `app-input`)
- Build: Vite
- Export: DomPDF + CSV

---

## 3. Status & Lifecycle Rules

Status standar lintas modul transaksi:
- `draft`
- `processed`
- `pending`
- `approved`
- `rejected`
- `final`

Rule:
- status `final` = view-only (tidak ada mutasi).

Quotation rule tambahan:
- edit saat `approved` oleh creator akan reset status ke `pending` (re-approval).
- `set final` dibatasi oleh permission workflow.
- approval diblokir jika validasi quotation belum selesai (`validation_status != valid` saat validation required).

---

## 4. Module Landscape

### 4.1 Master Data
- Destinations
- Vendor Management
- Activities
- Food & Beverages
- Hotels
- Airports
- Transports
- Tourist Attractions
- Currencies
- Users
- Roles
- Service Manager (module toggle)

### 4.2 Transaction Modules
- Customers
- Inquiries
- Itineraries
- Quotations
- Bookings
- Invoices

---

## 5. Access Control Architecture (Current)

### 5.1 Permission-first Enforcement

Semua CRUD dan aksi khusus diarahkan ke permission matrix.

Pattern utama:
- route access: `permission:module.{module}.access`
- per-action CRUD: `module.permission:{module}` (`create/read/update/delete`)
- policy model: permission-based checks

Owner-based mutation standard:
- untuk modul transaksi owner-centric (contoh Inquiry, Itinerary, Quotation, dan Booking), `update/delete` mengikuti rule creator-only:
  - `user` harus punya permission aksi terkait, dan
  - record wajib dimiliki user (`created_by` sama dengan user login).
- modul Invoice pada baseline saat ini bersifat read-only di Finance flow (`index/show`), tanpa endpoint mutasi data.

### 5.2 Super Admin Strategy

- Super Admin bypass via `Gate::before`.
- Informasi identitas Super Admin dimasking di UI user non-superadmin (`System`).
- Role/identitas Super Admin disembunyikan dari flow admin biasa.

### 5.3 Dashboard Routing

Dashboard redirect berbasis permission priority:
- `dashboard.superadmin.view`
- `dashboard.administrator.view`
- `dashboard.director.view`
- `dashboard.finance.view`
- `dashboard.reservation.view`
- `dashboard.manager.view`
- `dashboard.marketing.view`
- `dashboard.editor.view`

---

## 6. Quotation Validation (Current Behavior)

- Validator role by permission: `quotations.validate`.
- Scope item wajib validasi:
  - Hotel (`Hotel arranged by us`),
  - Activity,
  - Food & Beverage,
  - Island Transfer,
  - Transport,
  - Tourist Attraction.
- Save item via AJAX per-row.
- KPI progress update realtime.
- `Validate Quotation` hanya muncul saat progress 100%.
- Final approval guard aktif jika validation belum complete.
- Master rate sync dua arah:
  - update dari validation page -> update quotation item + source module,
  - update source module -> tampil sinkron di halaman validation.

---

## 7. UI Standards

### 7.1 Responsive
- Mobile/tablet wajib usable untuk aksi utama.
- Data besar: card/list pada mobile-tablet, table pada desktop (`xl+`).

### 7.2 Nominal Input
- Gunakan `x-money-input`.
- Currency badge left-affix.
- Tampilan grouped format, payload tetap numeric murni.
- Markup badge mengikuti `markup_type` (`%` atau currency symbol/code).

### 7.3 Date & Time Display
- Format tanggal wajib `YYYY-MM-DD`.
- Format tanggal+waktu wajib `YYYY-MM-DD (HH:ii)`.
- Berlaku untuk seluruh UI dan PDF.
- Formatter backend canonical: `\App\Support\DateTimeDisplay`.
- Render datetime di JavaScript wajib deterministic (tidak bergantung locale browser).
- CI guard: `.github/workflows/date-format-guard.yml` menjalankan `scripts/ci/check-date-format.sh`.

---

## 8. Database Safety Baseline

- `.env.testing` wajib dipisah dari `.env`.
- Test yang memakai `RefreshDatabase` tidak boleh mengarah ke DB utama.
- Command destruktif (`migrate:fresh`, `db:wipe`, `migrate:refresh`) hanya di environment aman + backup.

---

## 9. Performance Baseline

Shared request layer sekarang memakai cache/memoization untuk mengurangi query berulang:

- Schema check hot path: `\App\Support\SchemaInspector`.
- Module enabled map/list: `\App\Services\ModuleService`.
- Currency metadata/display options: `\App\Support\Currency`.
- Company branding/settings: `\App\Support\CompanySettingsCache`.
- Index stats cards: cache singkat pada `IndexStatsComposer`.
- Dashboard berat: cache aggregate singkat atau async widget pattern.

Invalidation wajib:

- module toggle -> `ModuleService::flushCache()`.
- currency mutation -> `\App\Support\Currency::flushCache()`.
- company settings update -> `CompanySettingsCache::flush()`.

Detail standar:
- `docs/technical/PERFORMANCE_OPTIMIZATION_STANDARD.md`

---

## 10. Canonical Documentation Map

Root source-of-truth:
- `README.md`
- `PROJECT_GUIDELINES.md`
- `PROJECT_KNOWLEDGE_BASE.md`
- `VOYEX_CRM_SYSTEM_ROADMAP.md`
- `VOYEX_CRM_AI_GUIDELINE.md`

Technical docs:
- `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`
- `docs/technical/ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
- `docs/technical/ISLAND_TRANSFER_MODULE.md`
- `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`
- `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md`
- `docs/technical/NOMINAL_INPUT_STANDARD.md`
- `docs/technical/I18N_TRANSLATION_STANDARD.md`
- `docs/technical/IMAGE_THUMBNAIL_STANDARD.md`
- `docs/technical/TECHNICAL_FIX_NOTES.md`
- `docs/technical/PERFORMANCE_OPTIMIZATION_STANDARD.md`

---

## 11. Maintenance Rule

Jika terjadi konflik dokumen, urutan prioritas:
1. `PROJECT_GUIDELINES.md`
2. `PROJECT_KNOWLEDGE_BASE.md`
3. `VOYEX_CRM_SYSTEM_ROADMAP.md`
4. `docs/core/LAYOUT_GUIDE.md`
5. Dokumen teknis lainnya

---

## Reservation Dashboard Notes

- Access: gated by permission `dashboard.reservation.view` and surfaced via dashboard redirect priority.
- Performance: heavy booking queries (status counts, monthly counts, weekly trends, top customers) are cached with a short TTL (120 seconds) using cache keys namespaced under `reservation:*` to reduce load during high traffic. Consider invalidating these keys in booking/quotation update hooks.
- Testing: feature test `tests/Feature/Dashboard/ReservationDashboardTest.php` added to assert permission enforcement and page rendering.

