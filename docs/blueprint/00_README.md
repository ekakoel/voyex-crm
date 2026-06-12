# Blueprint Index

1. [Operational Commercial Flow Refactor](01_operational_commercial_flow_refactor.md)

## Workflow Final
Inquiry -> Quotation Draft -> Quotation Validation -> Quotation Sent -> Customer/Agent Approved -> Booking Created -> Vendor Confirmation per Item -> Voucher Generated per Item -> Operation Running -> Actual Service Reconciliation -> Final Invoice -> Payment / Settlement -> Closed

## Source of Truth
- Inquiry: sumber kebutuhan customer/agent.
- Itinerary: rancangan perjalanan.
- Quotation: dokumen komersial dan harga.
- QuotationItem: snapshot harga/item penawaran.
- Booking: container operasional.
- BookingItem: snapshot item operasional.
- Voucher: bukti pemesanan vendor/provider.
- Adjustment: perubahan lapangan.
- Invoice: tagihan.
- Payment: pembayaran.
- Settlement: closing dan profit.

## Rule Penting
- Quotation yang sudah disetujui customer/agent tidak boleh diubah tanpa jejak.
- Jika ada perubahan, wajib buat revision atau adjustment log.
