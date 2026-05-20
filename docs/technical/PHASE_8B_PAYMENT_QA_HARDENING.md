# PHASE 8B-QA - Payment Module Stabilization and Edge Case Testing

Date: 2026-05-20

## Scope

Stabilisasi modul Payment setelah fondasi 8B selesai, tanpa implementasi Operation/Adjustment/Settlement.

## QA Review Result

1. Permission review:
- Permission payment yang direview: `payments.view`, `payments.create`, `payments.confirm`, `payments.reject`, `payments.cancel`.
- Role `Finance`, `Accountant`, `Manager`, `Director`, `Administrator`, `Super Admin` dipastikan memiliki akses payment sesuai seeder.
- `Reservation` tidak diberi permission confirm/reject/cancel payment.

2. Lifecycle guard review:
- Payment `confirmed` tidak bisa confirm ulang.
- Payment `rejected` tidak bisa confirm.
- Payment `cancelled` tidak bisa confirm.
- Invoice `paid/overpaid` hanya bisa menerima payment type `additional_payment`.
- Invoice `void/cancelled/draft` tidak bisa menerima payment.
- Nilai amount wajib > 0, tanggal wajib valid (FormRequest).

3. Invoice recalculation review:
- Hanya payment `confirmed` yang disum ke `invoice.paid_amount`.
- Payment `rejected/cancelled` diabaikan dari kalkulasi.
- `balance_amount = max(total_amount - paid_amount, 0)`.
- Overpayment men-set status invoice ke `overpaid`.

4. Upload proof review:
- Validasi file: `jpg,jpeg,png,pdf,webp`.
- Maks ukuran: 5MB.
- Path upload: `storage/app/public/payments/proofs`.
- Tidak ada proses overwrite/delete otomatis pada proof lama (aman secara default).
- Kebutuhan `php artisan storage:link` tetap berlaku untuk akses URL public.

5. Controller hardening:
- Error lifecycle dari service tidak lagi memunculkan 500 langsung.
- Ditangani dengan redirect back + error message.

6. UI guard:
- Tombol confirm/reject/cancel di detail payment mengikuti status **dan** permission.
- Tombol `Record Payment` di invoice detail mengikuti `payments.create`.

## Automated Tests Added

File:
- `tests/Feature/Finance/PaymentServiceTest.php`

Kasus yang diuji:
- create pending payment tidak mengubah invoice.
- confirm partial payment -> `partially_paid`.
- confirm full payment -> `paid`.
- confirm overpayment -> `overpaid`.
- reject/cancel payment tidak mengubah invoice paid/balance.
- tidak bisa confirm payment state yang invalid (rejected/cancelled/confirmed).
- tidak bisa create payment untuk invoice `draft/void/cancelled`.

## Remaining Deferred Items

- Deposit ledger / credit balance allocation.
- Refund accounting detail lintas invoice/payment.
- Settlement integration dan closing gate final.
