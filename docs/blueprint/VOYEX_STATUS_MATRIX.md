# VOYEX Status Matrix

Last Updated: 2026-05-25

## Inquiries
- new_request: inquiry baru masuk, belum diproses.
- need_customer_data: data customer belum lengkap, perlu dilengkapi dulu.
- registered: inquiry sudah tercatat valid di sistem.
- assigned: inquiry sudah di-assign ke PIC/handler.
- contacted: tim sudah melakukan kontak awal ke customer.
- waiting_customer: menunggu respon/keputusan dari customer.
- qualified: inquiry layak dilanjutkan ke tahap penawaran.
- unqualified: inquiry tidak layak dilanjutkan (terminal).
- itinerary_in_progress: penyusunan itinerary sedang berjalan.
- quotation_in_progress: penyusunan quotation sedang berjalan.
- quotation_sent: quotation sudah dikirim ke customer.
- under_negotiation: sedang tahap negosiasi dengan customer.
- accepted: quotation diterima customer.
- converted_to_booking: inquiry berhasil dikonversi menjadi booking (final).
- lost: opportunity hilang/tidak lanjut (terminal).
- cancelled: inquiry dibatalkan (terminal).
- expired: inquiry kadaluarsa melewati batas waktu (terminal).

## Quotation
- draft
- pending_validation
- validated
- sent
- Revision
- customer_approved
- booking_created
- in_operation
- completed
- cancelled
- lost

## Quotation Item
- active
- validated
- vendor_pending
- vendor_confirmed
- voucher_generated
- used
- cancelled_free
- cancelled_with_charge
- not_available
- replaced
- added_after_approval

## Booking
- created
- vendor_confirmation
- voucher_preparation
- ready_to_operate
- in_operation
- service_completed
- reconciliation
- invoiced
- closed
- cancelled

## Booking Item
- pending_vendor
- confirmed_by_vendor
- voucher_generated
- used
- not_used
- cancelled_free
- cancelled_with_charge
- replaced
- completed

## Voucher
- draft
- generated
- sent_to_vendor
- confirmed_by_vendor
- reissued
- cancelled
- used

## Invoice
- draft
- issued
- partially_paid
- paid
- revised
- void
- cancelled

## Invoice Type
- proforma
- final
- adjustment
- credit_note

## Legacy Mapping (Step 3)
- accepted -> customer_approved
- converted -> booking_created
- valid -> validated
- pending_validation -> pending_validation
- draft -> draft
- rejected -> lost
- final -> completed (domain meaning), but current operational bridge uses booking_created as conversion lock state.

## Inquiry Module Standard (2026-05-25)
- Assignment source of truth:
  - Primary operational ownership uses `handled_by`.
  - `assigned_to` remains user-facing and is synchronized to `handled_by` when claimed/assigned.
- Inquiry eligibility for quotation flow:
  - Inquiry must not be final (`converted_to_booking`).
  - Inquiry must be either:
    - handled by current user, or
    - unhandled (`handled_by`/`assigned_to` empty).
  - Inquiry must not already have a linked quotation.
- Inquiry dropdown behavior in quotation form:
  - Must include owned + unhandled inquiries only.
  - Must exclude inquiries that already have a quotation.
  - Must display single quotation availability count in option label for context.

## Quotation Module Standard (2026-05-25)
- Relational rule (mandatory):
  - One `Inquiry` can have exactly one `Quotation` (`1:1`).
  - Every `Quotation` must link to exactly one `Inquiry` (`quotations.inquiry_id` NOT NULL).
- Form mandatory fields:
  - `inquiry_id` required.
  - `customer_id` required.
  - `itinerary_id` optional.
- Inquiry consistency rule:
  - Selected inquiry and selected itinerary are independent form inputs.
  - Generating from itinerary must not clear or overwrite selected inquiry.
- Duration rule in create/edit form:
  - `duration_days` editable (minimum 1).
  - `duration_nights` readonly, always `max(duration_days - 1, 0)`.
  - If quotation is generated from itinerary, duration auto-syncs from itinerary.
- Service item rule:
  - Service items shown only from active master data.
  - Service item list filtered by selected destination and sorted A-Z.
  - Service item picker uses single input + dropdown reference.
  - Item label format:
    - with vendor: `item - vendor/provider - city`
    - without vendor: `item - city`

## Dev Changelog - 2026-05-25
- Refactor `QuotationController::availableInquiriesQuery`:
  - include inquiries handled by current user OR unhandled.
  - exclude inquiries that already have any linked quotation.
  - keep current quotation inquiry available during edit only.
- Refactor `QuotationController::assertInquiryEligibleForQuotationGeneration`:
  - reject terminal inquiries and inquiries already linked to another quotation.
  - enforce ownership rule: handled by user or unhandled.
- Enforced DB-level rule:
  - added migration `2026_05_25_120000_enforce_non_nullable_inquiry_id_on_quotations`.
  - backfilled legacy null `inquiry_id`, then enforced NOT NULL + FK.
  - added migration `2026_06_08_000000_enforce_one_to_one_inquiry_quotation`.
  - migration adds unique index on `quotations.inquiry_id` after blocking duplicate legacy links.
- Quotation form UX updates:
  - service item picker supports single-input dropdown reference.
  - service item labels standardized with city context.
  - duration days/nights input behavior standardized.

## Standard Docs
- Standards index: [docs/standards/README.md](/D:/Eka Koel/App/2026/crm-balikamitour/docs/standards/README.md)
- Inquiry module standard: [docs/standards/inquiry-standard.md](/D:/Eka Koel/App/2026/crm-balikamitour/docs/standards/inquiry-standard.md)
- Quotation module standard: [docs/standards/quotation-standard.md](/D:/Eka Koel/App/2026/crm-balikamitour/docs/standards/quotation-standard.md)
