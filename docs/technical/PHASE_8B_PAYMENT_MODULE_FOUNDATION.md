# PHASE 8B - Payment Module Foundation

Date: 2026-05-20

## Scope

Fase ini hanya membangun fondasi modul Payment agar Invoice bisa menerima pembayaran secara aman dan terukur.

Belum termasuk:
- Adjustment
- Settlement
- Closing
- Refund/deposit allocation accounting detail

## Implemented

1. Schema baru `payments` (migration baru, non-destructive):
- Relasi ke `invoices`
- Status: `pending`, `waiting_confirmation`, `confirmed`, `rejected`, `cancelled`
- Type: `down_payment`, `balance_payment`, `full_payment`, `additional_payment`, `refund`, `deposit`
- Audit fields: `created_by`, `confirmed_by`, `confirmed_at`, `rejected_by`, `rejected_at`, `rejection_reason`
- Field upload bukti: `proof_path`
- Index: `invoice_id`, `status`, `payment_date`, `invoice_id+status`

2. Model `Payment`:
- Relasi ke `Invoice`
- Relasi user creator/confirmer/rejector
- Helper lifecycle (`isPending`, `isConfirmed`, `canBeConfirmed`, dst)

3. Hardening `Invoice`:
- Relasi `payments()`
- Relasi `confirmedPayments()`
- Guard `canReceivePayment()` untuk mencegah posting tidak valid pada status invoice terlarang

4. Service layer `PaymentService`:
- `createPayment()`
- `confirmPayment()`
- `rejectPayment()`
- `cancelPayment()`
- `recalculateInvoicePaymentState()`
- `generatePaymentNumber()`

Semua aksi kritikal dibungkus DB transaction.

5. Posting rule:
- Hanya payment `confirmed` yang dihitung ke `invoice.paid_amount`
- `balance_amount` direkalkulasi dari `total_amount - paid_amount`
- Status invoice otomatis:
  - `paid_amount <= 0` -> `issued` (atau tetap `draft` jika sebelumnya draft)
  - `0 < paid_amount < total_amount` -> `partially_paid`
  - `paid_amount == total_amount` -> `paid`
  - `paid_amount > total_amount` -> `overpaid`

6. HTTP layer:
- `PaymentController` (index, create, store, show, confirm, reject, cancel)
- `StorePaymentRequest`
- `PaymentLifecycleActionRequest`
- Route finance payment + middleware permission

7. UI:
- Halaman payment list/create/show
- Integrasi di invoice detail:
  - tombol `Record Payment` jika invoice menerima payment
  - ringkasan payment + list payment terkait invoice

8. Permission:
- `payments.view`
- `payments.create`
- `payments.confirm`
- `payments.reject`
- `payments.cancel`

9. Activity log:
- Payment created/confirmed/rejected/cancelled
- Invoice payment state recalculated (saat terjadi perubahan state)

10. Multi-language baseline:
- Key baru payment ditambahkan ke `en`, `zh_Hans`, `zh_Hant` agar tidak ada missing phrase.

## Validation and Safety Notes

- Tidak ada perubahan migration lama.
- Data existing invoice tetap dipertahankan.
- Payment tidak mengubah invoice paid/balance sebelum status `confirmed`.
- Aksi delete payment tidak disediakan; lifecycle menggunakan reject/cancel.

## Manual Test Matrix

1. Buat invoice dari booking, lalu issue invoice.
2. Record payment status `pending`; pastikan paid/balance invoice belum berubah.
3. Confirm payment sebagian; status invoice jadi `partially_paid`.
4. Confirm payment pelunasan; status invoice jadi `paid`.
5. Tambah payment tambahan di invoice paid; status jadi `overpaid`.
6. Reject payment pending; pastikan paid/balance tidak terpengaruh.
7. Cancel payment pending; pastikan paid/balance tidak terpengaruh.
8. Cek activity log untuk event payment + recalc invoice.

## Deferred to Next Phases

- Alokasi overpayment ke deposit/refund
- Payment reconciliation ke settlement
- Blocking close booking berbasis settlement penuh
