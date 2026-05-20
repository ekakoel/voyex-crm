# MODULE IMPLEMENTATION CHECKLIST

## Customer / Agent

- [ ] Customer CRUD exists
- [ ] Agent/company type exists
- [ ] Contact person supported
- [ ] Duplicate phone/email/company validation
- [ ] Active/inactive status
- [ ] Customer linked to inquiry
- [ ] Customer linked to quotation/booking/invoice if needed

## Inquiry

- [ ] Inquiry CRUD exists
- [ ] Inquiry number auto-generated
- [ ] Customer/agent required
- [ ] Source field complete
- [ ] Assigned reservation exists
- [ ] Priority exists
- [ ] Deadline exists
- [ ] Follow-up history exists
- [ ] Communication history exists
- [ ] Create itinerary button exists
- [ ] Create quotation button exists
- [ ] Final statuses supported
- [x] Activity log exists

## Itinerary

- [ ] Itinerary CRUD exists
- [ ] Inquiry relationship exists
- [ ] Customer relationship exists or can be resolved from inquiry
- [ ] Day/item structure exists or equivalent structured detail exists
- [ ] Service item type exists
- [ ] Chargeable flag exists
- [ ] Include in quotation flag exists
- [ ] Duplicate itinerary exists
- [ ] PDF/preview exists
- [ ] Generate quotation exists
- [ ] Status lifecycle exists
- [ ] Locking after converted exists

## Quotation

- [ ] Quotation CRUD exists
- [ ] Inquiry relationship exists
- [ ] Itinerary relationship exists
- [ ] Quotation version exists
- [ ] Revision flow exists
- [ ] Items exist
- [ ] Contract rate snapshot exists
- [ ] Markup snapshot exists
- [ ] Discount exists
- [ ] Total/final amount calculated correctly
- [ ] PDF exists
- [ ] Sent status exists
- [ ] Accepted status exists
- [ ] Rejected/lost/expired flow exists
- [ ] Locking after accepted/converted exists

## Quotation Validation

- [x] Dedicated validation page exists
- [x] Route exists
- [x] Controller exists
- [x] Item modal exists
- [x] Vendor/provider/contact detail visible
- [x] Contract rate editable if allowed
- [x] Markup type/value editable if allowed
- [x] Rate validity checked
- [x] Bulk validate exists
- [x] Finalize validation exists
- [x] Prevent send if not validated (guarded at approval gate; sent route not exposed yet)
- [x] Validation log exists

## Booking

- [x] Booking module exists
- [x] Convert from accepted quotation exists
- [x] Booking stores quotation_id
- [x] Booking stores itinerary_id or snapshot
- [x] Booking items exist
- [x] Participant/pax data exists
- [x] Operation status exists
- [x] Payment status exists
- [x] Generate invoice action exists
- [x] Cancel booking action exists
- [x] Close booking action protected by settlement

## Invoice

- [x] Invoice module exists
- [x] Invoice linked to booking
- [x] DP invoice supported
- [x] Balance invoice supported
- [x] Full payment invoice supported
- [x] Additional charge invoice supported
- [x] Refund invoice/credit note supported
- [ ] Invoice PDF exists
- [x] Invoice status lifecycle exists
- [x] Issued invoice locked

## Payment

- [x] Payment module exists
- [x] Payment linked to invoice
- [x] Partial payment supported
- [x] Payment confirmation exists
- [x] Payment receipt upload exists
- [x] Invoice balance updates correctly
- [x] Overpayment detected
- [x] Refund supported
- [ ] Deposit/credit balance supported

Notes:
- Phase 8B-QA memastikan guard lifecycle payment, permission button UI, dan edge-case test service sudah berjalan.
- Phase 9B: dispatch dan SPK tersedia, driver/guide assignment masih text-based (belum master module).

## Operation

- [ ] Operation dashboard exists
- [x] Vendor confirmation exists
- [x] Driver assignment exists
- [x] Guide assignment exists
- [x] SPK exists
- [ ] WhatsApp share exists if needed
- [x] Service started/completed status exists
- [x] Issue report exists

## Adjustment

- [x] Adjustment module/table exists
- [x] Adjustment type exists
- [x] Amount/reason exists
- [x] Approval flow exists
- [x] Applied state exists
- [x] Creates additional invoice/refund/deposit if approved
- [x] Activity log exists

## Settlement

- [x] Settlement review page exists
- [x] Checks service completed
- [x] Checks invoice status
- [x] Checks payment balance
- [x] Checks adjustment pending
- [ ] Checks refund/deposit
- [x] Allows close only if settled

Notes Phase 12A-FIX (2026-05-21):
- Payment status canonical set sudah sinkron di model + status config.
- Settlement status badge mapping sudah eksplisit (tidak fallback generic untuk status known).
- Settlement feature test ditambahkan dengan guard skip SQLite + requirement MySQL test DB.

Notes Phase 12B (2026-05-21):
- Settlement gating dan negative-path checks tervalidasi di level service/test design.
- Eksekusi UAT manual multi-role penuh masih menunggu staging sign-off.
- Finance/Settlement integration test lane membutuhkan MySQL test DB; sqlite skip tetap expected.

Notes Phase 12C (2026-05-21):
- Final go-live blocker saat ini bukan fitur bisnis, tetapi eksekusi staging evidence.
- MySQL finance/settlement lane wajib dijalankan di environment dengan DB `voyex_crm_test`.
- Sign-off role-based UAT dan rollback drill wajib dilampirkan sebelum keputusan GO-LIVE.

Notes Phase 12D (2026-05-21):
- Semua artefak sign-off final sudah tersedia (decision report + evidence matrix + deployment result).
- Penutupan fase bergantung pada eksekusi nyata di staging/CI, bukan perubahan code tambahan.
- Tidak ada bug bisnis kritikal baru; blocker murni eksekusi environment.
