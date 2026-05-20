# VOYEX CRM ROADMAP CHECKLIST

## Phase 0 — Backup & Safety

- [ ] Backup database production/staging
- [ ] Pastikan branch kerja bukan langsung production
- [ ] Buat branch `audit/business-flow-alignment`
- [ ] Pastikan `.env` tidak ikut commit
- [ ] Pastikan `APP_DEBUG=false` di production
- [ ] Pastikan tidak ada debug route terbuka di production

## Phase 1 — Full Audit Existing System

- [ ] Audit semua migration core flow
- [ ] Audit model Customer / Inquiry / Itinerary / Quotation / Booking / Invoice / Payment
- [ ] Audit semua controller core flow
- [ ] Audit route web.php dan route name
- [ ] Audit blade form/list/detail tiap modul
- [ ] Audit permission dan module access
- [ ] Audit status yang dipakai di database
- [ ] Audit status yang dipakai di model constants
- [ ] Audit status yang dipakai di controller
- [ ] Audit status yang dipakai di blade filter/badge
- [ ] Audit command scheduler terkait inquiry/quotation
- [ ] Audit activity log coverage

## Phase 2 — Status Standardization

- [ ] Buat mapping status lama → status baru
- [ ] Buat config/enum untuk InquiryStatus
- [ ] Buat config/enum untuk ItineraryStatus
- [ ] Buat config/enum untuk QuotationStatus
- [ ] Buat config/enum untuk BookingStatus
- [ ] Buat config/enum untuk InvoiceStatus
- [ ] Buat config/enum untuk PaymentStatus
- [ ] Buat migration aman untuk ubah enum/status column menjadi string jika perlu
- [ ] Update form status options
- [ ] Update badge color mapping
- [ ] Update filter status
- [ ] Update dashboard query berdasarkan status baru

## Phase 3 — Inquiry Flow Alignment

- [ ] Inquiry wajib linked ke customer/agent
- [ ] Source inquiry lengkap
- [ ] Assigned reservation berjalan
- [ ] Follow-up history berjalan
- [ ] Inquiry bisa create itinerary
- [ ] Inquiry bisa create quotation tanpa itinerary jika perlu
- [ ] Inquiry final status terkunci
- [ ] Inquiry converted_to_booking hanya dari accepted quotation
- [ ] Tambahkan activity log untuk perubahan status inquiry

## Phase 4 — Itinerary as Planning Engine

- [ ] Itinerary linked ke inquiry/customer
- [ ] Itinerary punya day/item structure atau struktur alternatif yang stabil
- [ ] Itinerary item punya item_type
- [ ] Itinerary item punya service/vendor relation jika chargeable
- [ ] Itinerary item punya is_chargeable
- [ ] Itinerary item punya include_in_quotation
- [ ] Itinerary bisa duplicate
- [ ] Itinerary bisa preview/pdf
- [ ] Itinerary bisa generate quotation
- [ ] Itinerary converted_to_booking terkunci

## Phase 5 — Quotation Engine

- [ ] Quotation bisa dibuat dari inquiry
- [ ] Quotation bisa dibuat dari itinerary
- [ ] Quotation punya version
- [ ] Quotation item menyimpan snapshot rate
- [ ] Quotation item menyimpan markup type/value
- [ ] Quotation item menyimpan subtotal
- [ ] Quotation total/final amount akurat
- [ ] Quotation revision tidak menimpa accepted quotation
- [ ] Quotation accepted terkunci
- [ ] Quotation converted ke booking terkunci

## Phase 6 - Quotation Validation

- [x] Validate Quotation button tersedia di detail quotation
- [x] Halaman validation terpisah
- [x] Semua item chargeable muncul di validation page
- [x] Modal detail vendor/provider/contact tampil
- [x] Contract rate bisa dicek/update sesuai rule
- [x] Markup type/value bisa dicek/update sesuai rule
- [x] Rate validity dicek
- [x] Validated_by dan validated_at tersimpan
- [x] Quotation tidak bisa sent jika item belum valid (approval gate)
- [x] Validation log tersedia

## Phase 7 - Booking Conversion

- [x] Convert to booking hanya dari quotation accepted
- [x] Booking menyimpan quotation_id
- [x] Booking menyimpan itinerary_id jika ada
- [x] Booking mengambil snapshot service/item
- [x] Booking punya participant/pax data
- [x] Booking punya payment status
- [x] Booking punya operation status
- [x] Booking bisa generate invoice
- [x] Booking tidak bisa closed tanpa settlement

## Phase 8 - Invoice & Payment

- [x] Invoice bisa DP
- [x] Invoice bisa balance
- [x] Invoice bisa full payment
- [x] Invoice bisa additional charge
- [x] Invoice bisa cancellation fee
- [x] Invoice bisa refund
- [x] Payment linked ke invoice
- [x] Payment partial berjalan
- [x] Payment confirmation berjalan
- [x] Invoice balance otomatis benar
- [x] Overpayment terdeteksi
- [ ] Overpayment bisa refund/deposit

Catatan QA:
- Phase 8B-QA hardening selesai pada 2026-05-20 (guard lifecycle + test edge-case + UI permission guard).

## Phase 9 — Operation / Service Date

- [x] Booking bisa ready_to_operate
- [x] Vendor confirmation checklist
- [x] Driver assignment
- [x] Guide assignment
- [x] SPK generation
- [ ] Share schedule WhatsApp jika tersedia
- [x] Service started
- [x] Service completed
- [x] Issue report

## Phase 10 — Adjustment & Settlement

- [x] Booking adjustment tersedia
- [x] Adjustment type tersedia
- [x] Adjustment amount/reason tersedia
- [x] Approval adjustment tersedia
- [x] Approved adjustment bisa membuat invoice tambahan/refund/deposit
- [x] Settlement page tersedia
- [x] Settlement cek invoice/payment/adjustment/service
- [x] Booking bisa closed jika settled

## Phase 11 — Production Readiness

- [ ] Semua route utama bisa dibuka sesuai role
- [ ] Tidak ada error 500 di core flow
- [ ] Vite build tersedia
- [ ] Storage link aman
- [ ] Queue/scheduler berjalan jika dipakai
- [ ] Backup berjalan
- [ ] Log error bersih
- [ ] Permission role sesuai kebutuhan
- [ ] UAT selesai





Catatan Phase 12A-FIX (2026-05-21):
- APP_DEBUG/production safety sudah didokumentasikan (operational requirement, server-side enforcement).
- Route location/resolve-google-map sudah diproteksi permission granular.
- Konsistensi status payment + settlement badge mapping sudah diselaraskan di code.
- Finance/Settlement integration test tetap membutuhkan MySQL test DB (SQLite skip documented).

Catatan Phase 12B (2026-05-21):
- UAT end-to-end checklist sudah dibuat dan validasi awal dilakukan (hasil: PARTIAL).
- Go-live masih tertahan karena MySQL test lane dan manual UAT staging belum complete.
- Production readiness belum boleh ditandai DONE sampai sign-off UAT bisnis.

Catatan Phase 12C (2026-05-21):
- Staging go-live rehearsal docs sudah dibuat (rehearsal, sign-off, MySQL lane, rollback runbook).
- MySQL test lane di environment ini masih BLOCKED (DB/dependency tooling environment).
- Multi-role manual UAT staging masih BLOCKED menunggu eksekusi role owner di staging.
- Go-live final masih NO-GO sementara sampai blocker staging ditutup.

Catatan Phase 12D (2026-05-21):
- Final staging sign-off docs sudah dibuat.
- MySQL lane masih BLOCKED di environment ini karena dependency install/test tooling tidak bisa dieksekusi penuh.
- Manual multi-role UAT dan deployment+rollback drill masih menunggu evidence staging host.
- Status go-live tetap NO-GO sementara sampai blocker mandatory ditutup.
