# VOYEX CRM — Quotation Status Flow

Dokumen ini menjelaskan alur status quotation dari awal sampai akhir, termasuk hubungan dengan validation, follow-up, customer response, revision, booking, invoice, payment, dan operation.

Tujuan utama flow ini adalah memastikan setiap quotation memiliki status yang jelas, action yang tepat, dan tidak menampilkan tombol/action yang tidak dibutuhkan.

---

## 1. Prinsip Utama

Quotation tidak hanya dianggap sebagai dokumen harga, tetapi sebagai commercial workflow.

Flow utama:

```text
Inquiry
→ Itinerary / Direct Quotation
→ Quotation Draft
→ Validation
→ Ready to Send
→ Sent to Customer/Agent
→ Follow-up / Customer Response
→ Revision / Approved / Pending / Lost / Cancelled
→ Booking
→ Invoice
→ Payment
→ Operation
→ Final Invoice
→ Completed
```

Setiap perubahan status harus:

```text
1. Melalui service layer / workflow service.
2. Dicatat ke quotation_status_logs.
3. Mengupdate current_stage dan next_action.
4. Menampilkan action button sesuai status.
5. Tidak menghapus histori quotation lama.
```

Catatan penting implementasi saat ini:

```text
Approval internal antar user (contoh: rule "minimum two non creator", remaining approvals, waiting for approver)
sudah dinonaktifkan dari Quotation Detail dan tidak lagi menjadi syarat workflow quotation.

Approval yang tetap berlaku adalah approval dari customer/agent (commercial approval),
bukan approval internal antar user.
```

---

## 2. Status Utama Quotation

| Status | Fungsi |
|---|---|
| `draft` | Quotation baru dibuat dan masih disusun |
| `need_validation` | Quotation menunggu validasi item/harga/vendor |
| `ready_to_send` | Quotation siap dikirim ke customer/agent |
| `sent` | Quotation sudah dikirim ke customer/agent |
| `revision_requested` | Customer/agent meminta revisi dan perlu diproses |
| `under_revision` | Customer/agent meminta revisi |
| `need_revalidation` | Quotation perlu validasi ulang |
| `approved` | Quotation disetujui customer/agent |
| `converted_to_booking` / `booking_in_progress` | Quotation sudah masuk proses booking |
| `booking_issue` | Booking mengalami kendala dan butuh revisi |
| `invoiced` | Invoice awal sudah dibuat |
| `waiting_payment` | Menunggu DP/pelunasan |
| `pending` | Menunggu respon/payment/follow-up lanjutan |
| `in_operation` | Service sedang berjalan |
| `operation_adjustment` | Ada perubahan aktual saat operation |
| `finalized` | Data final sudah dikunci |
| `completed` | Semua proses dan pembayaran selesai |
| `cancelled` | Quotation dibatalkan |
| `lost` | Quotation tidak lanjut / no response / rejected |

Legacy status yang tetap dibaca oleh sistem:

```text
pending_validation -> need_validation
pending_revalidation -> need_revalidation
customer_approved -> approved
booking_created -> converted_to_booking
```

Normalisasi data production harus dilakukan eksplisit dengan:

```bash
php artisan quotations:normalize-status --dry-run
php artisan quotations:normalize-status --apply
```

---

## 3. Status Dimension Pendukung

Selain `status`, quotation juga menggunakan status tambahan agar workflow lebih jelas.

| Field | Fungsi |
|---|---|
| `validation_status` | Status validasi item quotation |
| `send_status` | Status pengiriman quotation |
| `approval_status` | Status persetujuan customer/agent |
| `booking_status` | Status proses booking |
| `invoice_status` | Status invoice |
| `payment_status` | Status pembayaran |
| `operation_status` | Status operasional/service |
| `follow_up_status` | Status follow-up customer/agent |
| `current_stage` | Posisi workflow saat ini |
| `next_action` | Action berikutnya yang harus dilakukan |
| `handled_by` | User reservation yang menangani quotation |

---

## 3.1 Versioned Itinerary-Quotation Revision (Implemented Foundation)

Untuk menjaga histori perubahan lebih jelas, pondasi berikut sudah disiapkan:

```text
Itinerary dan Quotation direvisi berbasis versioning (chain), bukan overwrite data lama.
```

### Itinerary versioning foundation

- Kolom tambahan itinerary:
  - `revision_of_id`
  - `revision_number`
  - `revision_reason`
  - `revised_from_quotation_id`
- Ditambah foreign key/index agar chain revisi mudah ditelusuri.

### Orchestration foundation

- Service baru: `ItineraryQuotationRevisionOrchestrator`
  - `startRevisionFromQuotation()`
  - `finalizeItineraryRevision()`
- Tujuan:
  - revisi itinerary menjadi langkah awal revisi commercial quotation
  - setelah itinerary selesai disimpan, service item quotation dapat disinkronkan otomatis
  - item yang sama tetap mempertahankan validasi
  - item baru/berubah ditandai untuk revalidation

### Feedback guard (implemented)

- Saat save itinerary revision dengan konteks quotation (`return_to_quotation_revise=1`):
  - jika auto sync quotation berhasil: flash success `Itinerary revision saved. Continue revising quotation items.`
  - jika auto sync tidak berjalan: flash error yang jelas bahwa sinkron otomatis tidak terjadi dan item quotation perlu direvisi manual.
- Halaman edit itinerary menampilkan panel **Quotation Revision Context** agar user tahu itinerary tersebut terhubung ke quotation tertentu.

Catatan update implementasi:
- Wiring ke `Admin\ItineraryController@update` sudah aktif untuk itinerary revision yang memiliki `revised_from_quotation_id`.
- Saat itinerary revision disimpan, quotation terkait akan sinkron otomatis via orchestrator:
  - item yang signature-nya sama tetap dipertahankan (termasuk status validasi),
  - item baru/berubah ditandai perlu validasi,
  - item yang tidak lagi relevan ditandai `replaced` (tidak dihapus keras).

---

## 4. Flow Status Utama

### 4.1 Draft

```text
draft
```

Kondisi:
- Quotation baru dibuat dari Inquiry atau Itinerary.
- Item masih bisa diedit.
- Belum boleh dikirim ke customer/agent.

Action yang tersedia:
```text
Edit Quotation
Submit for Validation
```

Next status:
```text
draft → pending_validation
```

---

### 4.2 Pending Validation

```text
pending_validation
```

Kondisi:
- Quotation sudah siap diperiksa.
- Item/harga/vendor/markup/contract rate perlu divalidasi.

Action yang tersedia:
```text
Validate Quotation
Edit Quotation
```

Jika sebagian item valid:

```text
validation_status = partial
```

Jika semua item valid:

```text
status = validated
validation_status = valid
current_stage = quotation_validated
next_action = prepare_to_send
```

Next status:
```text
pending_validation → validated
```

---

### 4.3 Validated

```text
validated
```

Kondisi:
- Semua item sudah valid.
- Quotation boleh disiapkan untuk dikirim.

Action yang tersedia:
```text
Preview / Download PDF
Mark as Ready to Send
Mark as Sent
```

Next status:
```text
validated → ready_to_send
validated → sent
```

---

### 4.4 Ready to Send

```text
ready_to_send
```

Kondisi:
- Quotation siap dikirim.
- User bisa download PDF dan mengirim manual via WhatsApp, Email, WeChat, Line, Telegram, atau channel lain.

Action yang tersedia:
```text
Preview / Download PDF
Mark as Sent
```

Rule:
```text
Mark as Sent bukan tombol mengirim file.
Mark as Sent hanya menandai quotation sudah dikirim ke customer/agent.
```

Next status:
```text
ready_to_send → sent
```

---

### 4.5 Sent

```text
sent
```

Saat quotation ditandai sent, sistem mengisi:

```text
send_status = sent
approval_status = waiting_customer_response
follow_up_status = waiting_response
last_sent_at = now()
sent_count += 1
current_stage = customer_follow_up
next_action = follow_up_customer
```

Action yang tersedia:
```text
Preview / Download PDF
Add Follow-up
Add Customer Response
Set Pending
```

Secondary action:
```text
Mark as Lost
Mark as Cancelled
```

Next status:
```text
sent → under_revision
sent → customer_approved
sent → pending
sent → lost
sent → cancelled
sent → pending_revalidation
```

---

## 5. Follow-up Flow

Follow-up dipisah menjadi 2 form berbeda.

### 5.1 Follow-up Form

Digunakan saat reservation menghubungi customer/agent.

Form:
```text
Channel
Follow-up At
Follow-up Note
```

Rule:
```text
Follow-up hanya boleh 1 kali per hari per quotation.
Jika hari ini sudah follow-up, action Add Follow-up disembunyikan sampai hari berikutnya.
next_follow_up_at diisi otomatis oleh sistem: follow_up_at + 1 hari.
```

Status setelah follow-up:

```text
follow_up_status = followed_up
last_followed_up_at = now()
follow_up_count += 1
next_follow_up_at = follow_up_at + 1 day (auto)
approval_status tetap waiting_customer_response
status tetap sent / pending
```

Follow-up tidak langsung membuat quotation approved, cancelled, atau revision.

Jika follow-up terlewat:

```text
Jika next_follow_up_at sudah lewat hari dan tidak ada follow-up pada hari tersebut:
follow_up_status = follow_up_overdue
```

Istilah yang dipakai di sistem: `follow_up_overdue`.

### 5.2 Customer Response Form

Digunakan saat customer/agent memberikan jawaban.

Form:
```text
Response Channel
Response Status
Response Note
```

`Response At` diisi otomatis oleh sistem saat response disimpan (timestamp server).

Response status:
```text
revision_requested
approved
cancelled
rejected
```

Mapping response:

| Response | Status Quotation | Next Action |
|---|---|---|
| `revision_requested` | `under_revision` | `revise_quotation` |
| `approved` | `customer_approved` / `approved` | `create_booking` |
| `cancelled` | `cancelled` | `none` |
| `rejected` | `lost` | `none` |

---

## 6. Revision Flow

### 6.1 Under Revision

```text
under_revision
```

Kondisi:
- Customer/agent meminta perubahan.
- Perubahan bisa berupa tambah item, hapus item, ganti item, ubah tanggal, ubah pax, ubah itinerary, ubah hotel/transport, atau ubah budget.

Action yang tersedia:
```text
Revise Quotation
View Customer Requested Changes
Submit for Revalidation
```

Customer requested changes harus muncul dari:
```text
quotation_customer_responses
```

Filter:
```text
requires_revision = true
is_used_for_revision = false
```

Next status:
```text
under_revision → pending_revalidation
under_revision → pending_validation
```

### 6.2 Pending Revalidation

```text
pending_revalidation
```

Kondisi:
- Ada perubahan item/harga/vendor.
- Validity date habis.
- Booking issue membutuhkan perubahan item.

Action yang tersedia:
```text
Revalidate Quotation
View Expired / Changed Items
Edit Quotation
```

Next status:
```text
pending_revalidation → validated
pending_revalidation → ready_to_send
pending_revalidation → sent
```

---

## 7. Approval Flow

### 7.1 Customer Approved

```text
customer_approved
```

Kondisi:
- Customer/agent sudah menyetujui quotation.
- Quotation tidak boleh diedit langsung.
- Jika ada perubahan setelah approved, buat revision.

Action yang tersedia:
```text
Create Booking
Preview / Download PDF
Create Revision
```

Next status:
```text
customer_approved → booking_created
customer_approved → booking_in_progress
```

---

## 8. Booking Flow

### 8.1 Booking Created / Booking In Progress

```text
booking_created
booking_in_progress
```

Kondisi:
- Booking dibuat dari approved quotation.
- Reservation mulai menghubungi vendor/provider.
- Booking item dibuat dari quotation item.

Action yang tersedia:
```text
View Booking
Vendor Confirmation
View Voucher
```

Next status:
```text
booking_in_progress → invoiced
booking_in_progress → booking_issue
booking_in_progress → in_operation
```

### 8.2 Booking Issue

```text
booking_issue
```

Kondisi:
- Vendor/provider tidak tersedia.
- Item perlu diganti.
- Harga berubah.
- Ada pengurangan/penambahan service item karena kendala booking.

Action yang tersedia:
```text
Create Revision from Booking Issue
Revalidate Replacement Items
Add Customer Response
Notify Customer / Agent
```

Next status:
```text
booking_issue → under_revision
booking_issue → pending_revalidation
```

---

## 9. Invoice and Payment Flow

### 9.1 Invoiced

```text
invoiced
```

Kondisi:
- Invoice awal sudah dibuat dari booking/quotation approved.

Action yang tersedia:
```text
View Invoice
Send Invoice
Record Payment
```

Next status:
```text
invoiced → waiting_payment
invoiced → in_operation
```

### 9.2 Waiting Payment

```text
waiting_payment
```

Kondisi:
- Menunggu DP atau pelunasan.
- Jika payment melewati due date, quotation bisa menjadi pending/overdue.
- Jika validity_date habis sebelum payment, quotation butuh revalidation.

Action yang tersedia:
```text
View Invoice
Record Payment
Send Payment Reminder
Set Pending
```

Next status:
```text
waiting_payment → in_operation
waiting_payment → pending
waiting_payment → completed
```

---

## 10. Operation Flow

### 10.1 In Operation

```text
in_operation
```

Kondisi:
- Wisatawan sedang menggunakan layanan.
- Service item bisa berubah berdasarkan kondisi lapangan.

Action yang tersedia:
```text
Add Operation Adjustment
View Booking Items
Finalize Operation
```

Possible changes:
```text
Item used
Item not used
Cancel without charge
Cancel with charge
Last minute added item
Replace item
Price adjustment
Quantity adjustment
```

Next status:
```text
in_operation → operation_adjustment
in_operation → finalized
```

### 10.2 Operation Adjustment

```text
operation_adjustment
```

Kondisi:
- Ada perubahan aktual di lapangan.
- Invoice final harus mengikuti actual usage.

Action yang tersedia:
```text
Review Operation Adjustments
Generate Final Invoice
Notify Customer / Agent
```

Next status:
```text
operation_adjustment → finalized
```

---

## 11. Finalization Flow

### 11.1 Finalized

```text
finalized
```

Kondisi:
- Booking sudah difinalisasi.
- Actual usage sudah dikunci.
- Final invoice siap/terbit.

Action yang tersedia:
```text
View Final Invoice
Record Final Payment
Close Settlement
```

Next status:
```text
finalized → completed
```

### 11.2 Completed

```text
completed
```

Kondisi:
- Semua service selesai.
- Semua invoice sudah lunas.
- Tidak ada outstanding payment.
- Jika ada overpayment, sudah masuk deposit.

Action yang tersedia:
```text
View Summary
Download Final Documents
```

Tidak boleh ada action mutasi utama.

---

## 12. Lost and Cancelled Flow

### 12.1 Lost

```text
lost
```

Kondisi:
- Customer/agent tidak lanjut.
- Rejected.
- No response setelah batas follow-up.
- Masih sebelum service date atau opportunity dianggap gagal.

Action yang tersedia:
```text
View Summary
Duplicate / Create New Quotation
```

Tidak boleh ada action mutasi utama kecuali reopen jika fitur reopen dibuat dengan approval khusus.

### 12.2 Cancelled

```text
cancelled
```

Kondisi:
- Customer/agent membatalkan.
- Service date sudah lewat tanpa approval/booking/payment.
- Quotation dibatalkan oleh user berwenang.

Action yang tersedia:
```text
View Summary
Duplicate / Create New Quotation
```

Tidak boleh ada action mutasi utama kecuali reopen jika fitur reopen dibuat dengan approval khusus.

---

## 13. Automation Rule

Automation tidak boleh langsung mengubah status penting tanpa notifikasi review kepada reservation.

### 13.1 Follow-up Due

Jika:

```text
status in sent, pending
next_follow_up_at <= now
belum approved/cancelled/lost/completed
belum follow-up pada hari ini
```

Maka:

```text
follow_up_status = follow_up_due
buat notification quotation_follow_up_due untuk handled_by
status utama tidak berubah
```

Jika next_follow_up_at terlewat hari (missed day):

```text
follow_up_status = follow_up_overdue
buat notification quotation_follow_up_overdue untuk handled_by
status utama tidak berubah
```

Jika hari ini sudah follow-up:

```text
notification follow-up due/overdue tidak ditampilkan
action Add Follow-up tidak ditampilkan
```

### 13.2 No Response 3 Days

Jika:

```text
last_sent_at <= now - 3 days
belum ada customer response
no_response_warning_at is null
```

Maka:

```text
buat notification quotation_no_response_warning
set no_response_warning_at = now()
status utama tidak langsung berubah
```

Jika setelah warning tetap tidak ada action:

```text
status = pending
follow_up_status = pending_no_response
auto_status_reason = no_response_after_warning
```

### 13.3 Validity Date Expired

Jika:

```text
validity_date < today
status belum approved/booking/completed/cancelled/lost
```

Maka:

```text
status = pending_revalidation
validation_status = needs_revalidation / pending
current_stage = quotation_revalidation
next_action = revalidate_quotation
buat notification quotation_validity_expired
```

### 13.4 Service Date Risk

Jika:

```text
service_date <= today + 2 days
status belum approved/booking/completed/cancelled/lost
```

Maka:

```text
buat notification quotation_service_date_risk
status utama tidak langsung berubah
```

### 13.5 Service Date Passed

Jika:

```text
service_date < today
status belum approved/booking/completed/cancelled/lost
service_date_warning_at is null
```

Maka:

```text
buat notification quotation_auto_status_review_required
set service_date_warning_at = now()
status utama tidak langsung cancelled/lost
```

Jika setelah warning tetap tidak ada action:

```text
status = cancelled / lost sesuai business rule
auto_status_reason = service_date_passed_without_approval
```

---

## 14. Action Button Mapping

| Condition | Actions |
|---|---|
| `draft` | Edit, Submit Validation |
| `pending_validation` / validation `pending` | Validate Quotation, Edit |
| validation `partial` | Continue Validation |
| `validated` / `ready_to_send` | Preview/Download PDF, Mark as Sent |
| `sent` | Add Follow-up, Add Customer Response, Preview/Download PDF, Set Pending |
| `under_revision` | Revise Quotation, View Requested Changes, Submit Revalidation |
| `pending_revalidation` | Revalidate Quotation, Edit Quotation |
| `customer_approved` / `approved` | Create Booking, Preview/Download PDF, Create Revision |
| `booking_created` / `booking_in_progress` | View Booking, Vendor Confirmation |
| `booking_issue` | Create Revision from Booking Issue, Revalidate Replacement Items |
| `invoiced` | View Invoice, Send Invoice, Record Payment |
| `waiting_payment` | View Invoice, Record Payment, Send Payment Reminder |
| `in_operation` | Add Operation Adjustment, View Booking, Finalize Operation |
| `operation_adjustment` | Review Adjustments, Generate Final Invoice |
| `finalized` | View Final Invoice, Record Final Payment |
| `completed` | View Summary, Download Final Documents |
| `lost` | View Summary, Duplicate / Create New Quotation |
| `cancelled` | View Summary, Duplicate / Create New Quotation |

---

## 15. Status Flow Diagram

```text
draft
  ↓
pending_validation
  ↓
validated
  ↓
ready_to_send
  ↓
sent
  ├─→ under_revision
  │      ↓
  │   pending_revalidation
  │      ↓
  │   validated / ready_to_send
  │      ↓
  │   sent
  │
  ├─→ customer_approved
  │      ↓
  │   booking_created / booking_in_progress
  │      ├─→ booking_issue
  │      │      ↓
  │      │   under_revision / pending_revalidation
  │      │
  │      └─→ invoiced
  │             ↓
  │          waiting_payment
  │             ↓
  │          in_operation
  │             ↓
  │          operation_adjustment
  │             ↓
  │          finalized
  │             ↓
  │          completed
  │
  ├─→ pending
  │      ├─→ sent / follow-up again
  │      ├─→ pending_revalidation
  │      └─→ lost
  │
  ├─→ lost
  └─→ cancelled
```

---

## 16. Final Completion Rule

Quotation hanya boleh menjadi `completed` jika:

```text
1. Customer/agent approved.
2. Booking selesai.
3. Service/operation selesai.
4. Invoice final sudah dibuat.
5. Semua payment lunas.
6. Tidak ada outstanding balance.
7. Overpayment sudah masuk deposit jika ada.
8. Semua adjustment sudah diselesaikan.
```

Jika salah satu belum selesai, jangan set `completed`.

---

## 17. Locked Status Rule

Status berikut tidak boleh diedit langsung:

```text
customer_approved
booking_created
booking_in_progress
invoiced
in_operation
finalized
completed
cancelled
lost
```

Jika perlu perubahan, gunakan:

```text
Create Revision
Create Revision from Booking Issue
Operation Adjustment
Invoice Revision
```

---

## 18. Quotation Handler Action Rule

Semua action pada quotation hanya boleh dilakukan oleh user yang menjadi PIC/handler quotation.

Prioritas penentuan handler:

```text
1. quotations.handled_by
2. inquiry.handled_by
3. inquiry.assigned_to
4. quotations.created_by
5. inquiry.created_by
```

Aturan:

```text
- User handler dapat melihat detail dan menjalankan action sesuai status workflow.
- User bukan handler hanya boleh melihat detail quotation.
- Tombol/action mutasi tidak boleh ditampilkan untuk non-handler.
- Controller/service tetap wajib melakukan permission guard agar action tidak bisa ditembak langsung lewat URL.
- Permission role saja tidak cukup; user juga harus merupakan handler quotation.
- Pengecualian harus ditulis eksplisit di dokumen workflow dan diimplementasikan via policy, bukan hardcode di Blade.
```

Action yang termasuk mutasi:

```text
edit quotation
revise quotation
validate quotation
mark as sent
add follow-up
add customer response
set pending
mark lost/cancelled
mark approved
create booking
cancel quotation item
mark response used for revision
```

---

## 19. Recommended Status Order

Urutan umum status:

```text
draft
pending_validation
validated
ready_to_send
sent
under_revision
pending_revalidation
customer_approved
booking_created
booking_in_progress
booking_issue
invoiced
waiting_payment
pending
in_operation
operation_adjustment
finalized
completed
lost
cancelled
```

Catatan:
`pending`, `lost`, dan `cancelled` bukan selalu linear. Status ini adalah cabang kondisi berdasarkan response customer, follow-up, validity date, service date, atau payment.

---

## 20. Change Log (Daily Follow-up Rule)

Update implementasi:

```text
Tanggal: 2026-05-26
Perubahan:
- Follow-up form disederhanakan: hanya Channel, Follow-up At, Follow-up Note.
- Add Follow-up dibatasi 1x per hari per quotation.
- next_follow_up_at otomatis +1 hari dari follow_up_at.
- Jika follow-up hari ini sudah dilakukan: tombol Add Follow-up dan notifikasi due/overdue disembunyikan.
- Jika hari follow-up terlewat: follow_up_status menjadi follow_up_overdue.
- Customer response form disederhanakan: hanya Response Channel, Response Status, Response Note.
- Response Status dibatasi: revision_requested, approved, cancelled, rejected.
- Response At diisi otomatis oleh server saat response disimpan.
- Jika Response Status = revision_requested, quotation otomatis dianggap need revision (under_revision).
- Action Revise Quotation diarahkan ke halaman revisi khusus (`quotations.revise`).
- Pada halaman revisi, handler dapat menambah, mengurangi, mengganti, dan mengubah service item menggunakan form quotation existing.
- Saat revisi disimpan:
  - item tervalidasi yang tidak berubah tetap membawa status validasinya.
  - item baru atau pengganti yang butuh validasi masuk proses revalidation.
  - jika semua item valid atau tidak ada item yang butuh validasi, quotation menjadi ready_to_send.
  - jika masih ada item baru/pengganti yang perlu validasi, quotation menjadi pending_revalidation.
- Setelah ready_to_send, quotation dapat Preview/Download PDF lalu Mark as Sent kembali.
- Quotation action dikunci untuk handler/PIC quotation. Non-handler hanya melihat detail tanpa action mutasi.
```
