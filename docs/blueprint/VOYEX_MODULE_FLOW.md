# VOYEX Module Flow

Last Updated: 2026-05-21

## End-to-End Module Flow
1. Inquiry
2. Itinerary Planning
3. Quotation Drafting
4. Customer/Agent Approval
5. Booking Creation
6. Vendor Confirmation per Booking Item
7. Voucher Generation per Booking Item
8. Service Operation
9. Actual Service Reconciliation
10. Final Invoice
11. Payment Tracking
12. Settlement and Closure

## Responsibility Per Module
- Inquiry Module:
  - Menyimpan requirement awal customer/agent.
  - Menjadi entry point untuk itinerary dan quotation.
- Itinerary Module:
  - Menyusun rencana service/travel.
  - Menjadi dasar komposisi item quotation.
- Quotation Module:
  - Menyusun penawaran komersial dan nilai awal.
  - Menangani status approval customer/agent.
- Booking Module:
  - Menjadi kontainer eksekusi operasional setelah approval.
  - Menyimpan booking item snapshot dari quotation item.
- Vendor Confirmation Module:
  - Melacak availability dan confirmation per booking item.
  - Menangani replace/cancel jika vendor tidak tersedia.
- Voucher Module:
  - Menerbitkan bukti pemesanan vendor/provider per item.
  - Menyimpan lifecycle voucher termasuk reissue/cancel.
- Adjustment Module:
  - Mencatat perubahan lapangan dan impact biaya.
  - Menjadi input ke reconciliation dan invoice final.
- Reconciliation Module:
  - Menentukan actual used service per item.
  - Menjadi dasar final billing.
- Invoice Module:
  - Menerbitkan proforma/final/adjustment/credit note.
  - Menagih berdasarkan data reconciliation.
- Payment Module:
  - Mencatat pembayaran customer/agent dan outstanding.
- Settlement Module:
  - Menutup booking secara finansial.
  - Rekonsiliasi profit/loss akhir.

## Handover Contract Antar Modul
- Inquiry -> Itinerary: kebutuhan perjalanan.
- Itinerary -> Quotation: komposisi service + estimasi harga.
- Quotation -> Booking: item approved + harga baseline.
- Booking Item -> Vendor Confirmation: kebutuhan reservasi per item.
- Vendor Confirmation -> Voucher: item confirmed siap voucher.
- Booking + Adjustment -> Reconciliation: status actual service.
- Reconciliation -> Invoice: billable lines final.
- Invoice -> Payment -> Settlement: penerimaan kas dan closing.

## Guardrail Operasional
- Booking hanya boleh dibuat dari quotation yang sudah approved.
- Perubahan pasca approval tidak boleh overwrite tanpa revision/adjustment trail.
- Final invoice tidak boleh diterbitkan tanpa reconciliation final.
- Status Closed hanya berlaku setelah invoice/payment/settlement terpenuhi.
