# Voyex CRM – Cheat Sheet Operasional (Onboarding)

Ringkasan cepat untuk developer baru agar memahami alur bisnis, status, permission, dan gap roadmap paling kritis.

## 1. Flow Utama (End-to-End)
- Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice
- Output utama: Itinerary, Quotation, Invoice
- Semua fitur wajib mendukung alur ini dan menjaga konsistensi antar modul.

## 2. Status Standar (Wajib Konsisten)
Berlaku untuk: Inquiries, Itineraries, Quotations, Bookings, Invoices
- `draft`
- `processed`
- `pending`
- `approved`
- `rejected`
- `final`

Aturan penting:
- Jika `status = final`, data hanya boleh view (tidak boleh edit/hapus/aksi perubahan).

## 3. Permission & Role (Ringkas)
Akses modul dikontrol via middleware:
- `module:{moduleKey}`
- `permission:module.{moduleKey}.access`
- `module.permission:{moduleKey}`

Role kunci:
- Super Admin: kontrol penuh
- Administrator: user/role/vendor/service management
- Manager / Director: approval discount & quotation
- Reservation: inquiry/quotation/booking operasional
- Finance / Accountant: invoice + finance report
- Editor: katalog layanan & konten master

Guard spesifik:
- Itinerary/Quotation/Booking edit & delete: hanya creator (policy) + Super Admin override
- Inquiry update: hanya creator atau assigned_to
- Discount/promo: hanya Manager & Director
- Follow-up done: creator inquiry atau assigned_to

## 4. UI & Layout Rules (Kritis)
- Semua index page mengikuti template Customers:
  - Statistik: `app-card-grid`
  - Filter di kiri, table di kanan
  - Table wajib `app-card` + `app-table`
- Button wajib pakai `btn-*` standar
- Input (kecuali textarea) wajib pakai `app-input`
- Form submit lock + spinner aktif default, bisa override dengan:
  - `data-skip-spinner="1"` (button)
  - `data-disable-submit-lock="1"` (form)

## 5. Itinerary – Ringkasan Teknis
- Itinerary bersifat struktural per hari, bukan teks bebas.
- Data utama:
  - Tourist Attractions
  - Activities
  - Food & Beverage
  - Transport Units
  - Day Points
  - Accommodation stays
- Create/Edit:
  - Field status tidak ditampilkan, default `draft`
  - Setelah create/update, inquiry terkait otomatis `processed` (jika belum final)
- Destination + Duration Days + Nights ditampilkan satu baris
- Route Preview Map menggunakan Leaflet

## 6. Roadmap Gap Paling Kritis (Phase Berikutnya)
Prioritas bisnis & teknis:
1. Margin & Profit Calculation
   - Belum ada kalkulasi margin/profit terintegrasi.
2. Expense -> Profit Linking
   - Expense belum terhubung ke booking/quotation.
3. Audit Trail System (full)
   - Activity log masih partial.
4. Participant Management
   - Dibutuhkan untuk operasional booking lapangan.
5. Auto Reminder Engine
   - Reminder masih manual, belum automated.
6. Itinerary Templates + Versioning
   - Builder masih basic, belum ada template reuse/versioning.

## 7. Dokumen Referensi Utama
- `PROJECT_GUIDELINES.md`
- `VOYEX_CRM_AI_GUIDELINE.md`
- `VOYEX_CRM_SYSTEM_ROADMAP.md`
- `LAYOUT_GUIDE.md`
- `README.md`

---

Maintainer: Eka Koel
Last updated: 2026-03-23
