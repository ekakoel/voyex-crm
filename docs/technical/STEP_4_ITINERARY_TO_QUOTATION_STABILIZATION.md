# STEP 4 — Itinerary to Quotation Stabilization

Date: 2026-05-18

## Scope
- Fokus hanya pada stabilisasi workflow `Itinerary -> Quotation`.
- Tidak menambah modul Payment/Adjustment/Settlement/Closing.
- Tidak mengubah migration lama.

## Architecture Findings
- `Itinerary` = planning engine (day planner + service composition).
- `Quotation` = pricing engine (harga snapshot per item).
- Relasi inti:
  - `inquiries` <-> `itineraries` via `inquiry_itinerary_references` (pivot).
  - `quotations.itinerary_id` menjaga linkage itinerary asal.
  - `quotation_items` menyimpan snapshot detail harga dan deskripsi item saat generate/save.

## Source-of-Truth Decision (Inquiry -> Itinerary)
- Primary source-of-truth: `inquiry_itinerary_references`.
- Backward-compatibility fallback: `itineraries.inquiry_id` (legacy data lama).
- Implementasi fallback ditambahkan pada resolver inquiry dari itinerary di `QuotationController`.

## Stabilization Changes
1. Locked quotation safety:
   - Quotation status `sent`/`accepted` tidak diubah langsung saat update.
   - Update pada status locked akan membuat **quotation revision baru**.
2. Revision/version baseline:
   - Menambah kolom:
     - `quotations.revision_of_id` (self-reference nullable)
     - `quotations.revision_number` (default 1)
   - Revision baru otomatis:
     - status `revised`
     - validation reset ke `pending`
     - approval fields dikosongkan
     - item snapshot dibuat ulang dari payload update.
3. Chargeable-only generation:
   - Item hasil `ItineraryQuotationService::buildItems()` difilter agar hanya item chargeable (`unit_price > 0`) yang masuk quotation.
4. Itinerary update transactional + safe sync:
   - Persist itinerary update dibungkus `DB::transaction`.
   - Setelah update, sinkronisasi quotation linked dijalankan aman via `QuotationItinerarySyncService`:
     - hanya untuk quotation mutable (`draft/revised/pending_validation`),
     - tidak menyentuh quotation locked (`sent/accepted/converted`).
5. UI guard:
   - Tombol edit quotation disembunyikan untuk status `sent/accepted/converted` di index/show.
   - PDF visibility dipindah ke status `accepted/converted`.

6. Validation lock hardening:
   - Quotation validation actions (`save/finalize/update contact`) dikunci untuk status:
     - `sent`
     - `accepted`
     - `converted`
   - Mencegah perubahan validasi setelah quotation masuk fase non-editable.
   - Policy `validateQuotation` juga diselaraskan agar akses validation ditolak untuk status locked tersebut.

## Why
- Mencegah perubahan diam-diam pada quotation yang sudah dikirim/disetujui.
- Menjaga histori pricing dengan revision chain.
- Menjaga agar perubahan itinerary tidak merusak snapshot quotation yang sudah locked.

## Test Checklist
1. Generate quotation dari itinerary:
   - pastikan `itinerary_id` tersimpan.
   - pastikan item non-chargeable (harga 0) tidak ikut.
2. Ubah itinerary yang punya quotation `draft`:
   - pastikan linked quotation mutable tersync aman (tidak duplicate row).
3. Ubah quotation status `sent`/`accepted` lewat form update:
   - quotation lama tetap utuh,
   - quotation revision baru terbentuk (`revision_of_id`, `revision_number`),
   - item snapshot pada revision sesuai data terbaru.
4. Quotation status `converted`:
   - tetap locked (tidak bisa update langsung).
5. UI:
   - tombol edit tidak tampil pada `sent/accepted/converted`.
