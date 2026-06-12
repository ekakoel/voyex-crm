# VOYEX Operational Refactor Blueprint

Last Updated: 2026-05-21
Scope: Documentation blueprint only

## Tujuan Refactor
- Menyelaraskan VOYEX CRM dengan alur operasional travel agent yang realistis dari inquiry sampai closing.
- Memisahkan tanggung jawab domain komersial, operasional, dan finansial agar perubahan lapangan tetap terkontrol.
- Menjamin semua perubahan pasca approval tercatat, dapat diaudit, dan dapat direkonsiliasi ke invoice final.

## Workflow Utama
Inquiry -> Quotation -> Customer/Agent Approval -> Booking -> Vendor Confirmation per Item -> Voucher per Item -> Service Operation -> Actual Service Reconciliation -> Final Invoice -> Payment / Settlement -> Closed

## Source Of Truth Setiap Module
- Inquiry: sumber permintaan customer/agent dan kebutuhan awal.
- Itinerary: planning engine untuk merancang perjalanan/service sequence.
- Quotation: commercial agreement dan pricing plan sebelum eksekusi operasional.
- Quotation Item: snapshot item + harga dalam konteks quotation.
- Booking: operational execution container setelah quotation approved.
- Booking Item: snapshot operasional dari quotation item pada saat konversi booking.
- Voucher: bukti pemesanan/konfirmasi vendor-provider per booking item.
- Adjustment: catatan perubahan kondisi lapangan (cancel/add/replace/charge/refund).
- Invoice: billing document berdasarkan actual used service + adjustment.
- Payment: pembayaran customer/agent dan alokasi terhadap invoice.
- Settlement: closing, profit, dan final reconciliation.

## Aturan Quotation Revision
- Sebelum `customer_approved`, quotation dan item dapat diubah bebas sesuai proses internal.
- Setelah `customer_approved`, perubahan tidak boleh overwrite diam-diam.
- Perubahan pasca approval wajib dicatat sebagai revision/adjustment trail.
- Revision harus menyimpan: pelaku perubahan, waktu, alasan, before/after value, dan dampak nominal.

## Aturan Booking Item Snapshot
- Saat booking dibuat, booking item harus menyalin data minimum dari quotation item (service, qty, rate, tanggal/service window, vendor context).
- Snapshot booking item menjadi baseline operasional; perubahan berikutnya masuk melalui adjustment/replacement, bukan edit tanpa jejak.
- Relasi traceability wajib terjaga: booking item harus dapat ditelusuri ke quotation item asal.

## Aturan Vendor Confirmation
- Confirmation dilakukan per booking item, bukan per booking global.
- Item wajib melewati state vendor pending/confirmed/not available/replaced/cancelled.
- Jika vendor tidak tersedia, wajib ada reason code + opsi replace/cancel yang tercatat.

## Aturan Voucher Per Item
- Voucher dibuat per item setelah approval customer/agent dan proses reservation vendor berjalan.
- Voucher menyimpan snapshot operasional penting (vendor/provider, service date, service detail, passenger context jika relevan).
- Voucher dapat reissue bila ada perubahan, namun riwayat versi harus tercatat.

## Aturan Cancellation Policy
- Cancellation bisa berlaku di level item.
- Charge dapat berbentuk nominal tetap atau persentase sesuai policy vendor/provider.
- Setiap cancellation wajib menyimpan policy reference, basis perhitungan, dan nominal final charge.

## Aturan Booking Adjustment
- Adjustment mencakup: cancel item, add item, replace item, perubahan biaya, penalty, discount/refund.
- Adjustment wajib memiliki kategori, alasan bisnis, approval trail (jika diperlukan), dan efek nilai terhadap invoice.
- Adjustment menjadi input wajib ke proses reconciliation dan invoice final.

## Aturan Proforma Invoice dan Final Invoice
- Proforma invoice dapat diterbitkan lebih awal (fase awal) saat item sudah voucher-ready.
- Proforma bersifat sementara/revisable sampai actual service selesai direkonsiliasi.
- Final invoice harus dihitung dari actual used service + additional item + cancellation charge + adjustment - discount/refund.
- Setelah final invoice dan settlement selesai, dokumen finansial dikunci sesuai governance.

## Aturan Reconciliation Sebelum Final Invoice
- Reconciliation dilakukan per booking item: used / partially used / not used / cancelled.
- Seluruh adjustment pasca operasi wajib diselesaikan sebelum finalisasi invoice.
- Final invoice hanya boleh dibuat/diubah berdasarkan hasil reconciliation terakhir yang tervalidasi.
- Selisih komersial vs operasional harus terdokumentasi untuk kebutuhan audit dan profit analysis.
