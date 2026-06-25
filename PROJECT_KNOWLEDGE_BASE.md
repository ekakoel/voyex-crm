# Voyex CRM - Project Knowledge Base

Last Updated: 2026-06-25


Version: 2.9  
Date: 2026-06-25  
Status: Source of truth aktif

---

## 1. System Identity

Voyex CRM adalah sistem CRM travel agent end-to-end:

`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Voucher/Operation -> Invoice -> Payment -> Settlement -> Closed`

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

Vendor Management baseline:
- Vendors/Providers can represent one or more service capabilities.
- Supported vendor capabilities are:
  - `Transportation`
  - `Island Transfer`
  - `F&B`
  - `Activities`
- The canonical multi-type field is `vendors.types` (JSON array).
- The legacy `vendors.type` field remains as a first-selected fallback for compatibility.
- Vendor index KPI cards summarize filtered vendor distribution by capability.
- Because one vendor can have multiple capabilities, type KPI counts may intentionally overlap.

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

### 7.1a Index Page Baseline
- Baseline resmi seluruh halaman index/list modul adalah `resources/views/modules/customers/index.blade.php` (`Customers / Agents`).
- Urutan wajib index: KPI/Summary Cards jika relevan, compact filter card, data table/mobile card list, lalu pagination/empty state.
- Halaman index modul tidak memakai dedicated sidebar kanan/kiri; summary pendukung ditempatkan pada KPI atau main content.
- Pada desktop, input filter utama harus diusahakan satu baris horizontal selama jumlah field masih wajar.
- Filter/list index yang mendukung AJAX wajib mempertahankan atribut `data-service-filter-*`.

### 7.1b PDF Action & Itinerary PDF Baseline
- Detail-page PDF actions should use the label `Preview / Download PDF` and open in a new browser tab when used as a preview action.
- Itinerary PDF must be customer-readable: compact branding, metadata, trip summary, day-by-day timeline, transport unit summary, and additional information.
- PDF controllers should prepare sanitized rich-text payloads before rendering Blade templates.
- PDF image data should use thumbnail-first resolution and per-request data URI caching to avoid repeated storage reads during one PDF render.

### 7.1c Itinerary Day Planner Region Filtering
- Create/edit itinerary service-row `Region` options are destination-aware and filtered by the Basic Info destination.
- Region options should include `destination`, `city`, `province`, and `location` metadata so the client can filter without extra requests.
- If the Basic Info destination changes, an incompatible selected service-row region should be cleared before service item filtering runs.

### 7.1d Itinerary Basic Destination Source
- Create/edit itinerary Basic Info `Destination` autocomplete uses `destinations` master data from the database.
- Search may match `destinations.name`, `city`, or `province`, but selected dropdown values should return canonical `destinations.name`.
- Do not mix vendor, airport, attraction, or other service-module region values into the Basic Info destination dropdown.
- The empty focused/clicked destination input should list all destination names; typing filters the same master dataset without an arbitrary frontend/backend result cap.
- The destination suggestions endpoint must not reference removed result-limit variables; focused tests cover uncapped results and keyword filtering.
- The destination dropdown must update immediately while typing by using cached suggestions plus request-token invalidation so older focus/click responses cannot replace newer typed results.
- Day Planner `Attraction`, `Activity`, and `F&B` autocomplete dropdowns follow the same no-cap and instant-response rule: fetch all matching records for current destination/region/meal context, cache per context, filter cached data immediately while typing, and invalidate stale requests.

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
- `docs/technical/BOOKING_MODULE.md`
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

---

## 12. Booking Module (Current Behavior)

### 12.1 Booking Create/Edit Baseline

- Sumber booking hanya dari quotation yang eligible (approved + validation complete + belum memiliki booking, kecuali booking saat ini pada mode edit).
- Satu quotation hanya boleh terhubung ke satu booking (one-to-one operational baseline).
- Saat create/edit, harga item tampil mengikuti currency aktif user, namun nilai yang disimpan ke DB tetap canonical IDR.

### 12.2 Booking Service Item Workspace

- Operasional booking item dipusatkan di halaman `bookings.edit` melalui workspace service item.
- Per-item tersedia aksi:
  - Book service item,
  - Edit booking service,
  - Cancel item,
  - Voucher preview/PDF (setelah booked).
- Detail booking tetap menjadi halaman review/preview, bukan pusat mutasi item.

### 12.3 Booking Log & Voucher

- `booking_items` menyimpan linkage item quotation ke booking.
- `booking_item_booking_logs` menyimpan jejak operasional booking item (audit trail).
- `booking_item_vouchers` menyimpan snapshot voucher.
- Setelah proses booking service item sukses, voucher digenerate/refresh otomatis.

### 12.4 Service Item Naming Rule

Semua nama service item pada UI Booking wajib membawa konteks provider:
- service vendor/provider: `service name | vendor/provider name`,
- service hotel: `service name | hotel name`.

Tujuan: mengurangi ambiguity saat service name serupa.

### 12.5 Cancellation Policy vs Cancellation Fee

Separation of concern:
- `Cancellation Policy` = text/rich text referensi manusia,
- `Cancellation Fee` = rules terstruktur untuk prefill/kalkulasi operasional.

Rule fee terstruktur:
- `min_days_before`,
- `max_days_before`,
- `fee_type` (`fixed`/`percent`),
- `fee_value`,
- `description`.

### 12.6 Cancel Service Item Flow

Saat cancel item:
1. Modal menampilkan policy text bila tersedia.
2. Fee type + fee diprefill dari snapshot/rules fallback bila ada.
3. User bisa override manual.
4. Jika type `nominal`, input mengikuti currency aktif UI lalu disimpan canonical ke IDR.
5. Jika type `percent`, fee dihitung dari total item booking.
6. Jika service item belum punya default fee rules, input cancel user disimpan menjadi default rules service item terkait.
7. Jika policy text kosong, user dapat mengisi policy text langsung dari modal cancel dan nilainya disimpan ke service item terkait.

Khusus hotel:
- policy fee target berada di level `Hotel` (bukan per-room),
- policy text referensi dapat berasal dari `cancellation_policy`, `cancellation_policy_traditional`, `cancellation_policy_simplified`.

### 12.7 Booking Performance Baseline

Optimasi aktif:
- eager-load morph serviceable + relation provider/hotel untuk create/edit booking,
- fallback policy query diprecompute di controller (bukan query di Blade loop),
- fallback policy hanya dihitung untuk item tanpa snapshot rules,
- detail booking memakai `latestBookingLog` tanpa load full `bookingLogs` collection bila tidak diperlukan.

Lanjutan wajib:
- hindari query DB dalam loop Blade,
- gunakan map/precompute di controller untuk data turunan berat.
