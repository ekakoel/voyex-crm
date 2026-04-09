# Quotation Approval UAT Matrix

Dokumen ini untuk validasi cepat flow approval quotation yang baru:
- Wajib ada approval dari `Manager`
- Wajib ada approval dari `Director`
- Wajib ada minimal `1 Reservation lain (bukan creator quotation)`
- Barulah status quotation berubah menjadi `approved`

## Preconditions
1. Jalankan migration terbaru:
   - `php artisan migrate`
2. Sync permission role terbaru:
   - `php artisan db:seed --class=RolePermissionSeeder`
3. Siapkan minimal 4 user:
   - `Marketing` (creator quotation)
   - `Manager`
   - `Director`
   - `Reservation A`
   - `Reservation B` (untuk skenario non-creator reservation)

## Scenario Matrix

| No | Actor | Action | Expected Result |
|---|---|---|---|
| 1 | Marketing (creator) | Create quotation | Quotation tersimpan dengan status `pending` |
| 2 | Manager | Klik `Approve` pada quotation | Approval Manager tercatat, status masih `pending` |
| 3 | Director | Klik `Approve` pada quotation | Approval Director tercatat, status masih `pending` jika belum ada Reservation non-creator |
| 4 | Reservation non-creator | Klik `Approve` pada quotation yang sama | Approval Reservation tercatat, status berubah jadi `approved` |
| 5 | Director | Klik `Set Pending` | Semua approval log di-reset, status kembali `pending` |
| 6 | Manager/Director | Klik `Reject` | Status jadi `rejected`, approval log dibersihkan |
| 7 | Reservation creator quotation | Klik `Approve` (jika creator role Reservation) | Approval creator Reservation tidak dihitung sebagai syarat Reservation non-creator |
| 8 | Marketing (non approver) | Akses action approve | Ditolak (tidak punya role approval) |
| 9 | Semua role | Lihat panel Validation (Edit/Detail) | Checklist Manager/Director/Reservation tampil sesuai progres |
| 10 | Semua role terkait | Cek Approval Log | Menampilkan role approver, nama user, waktu approval |

## Fast Execution Order (Recommended)
1. Login Marketing, buat quotation baru.
2. Login Manager, approve.
3. Login Director, approve.
4. Login Reservation non-creator, approve.
5. Verifikasi status akhir `approved`.

## Negative Test (Recommended)
1. Buat quotation oleh user role Reservation A.
2. Manager approve.
3. Director approve.
4. Reservation A (creator) approve.
5. Verifikasi status tetap `pending`.
6. Reservation B approve.
7. Verifikasi status berubah `approved`.

## UI Checkpoint
Pastikan pada panel `Validation`:
1. Badge/checklist `Manager Approval` berubah `Done` saat manager approve.
2. Badge/checklist `Director Approval` berubah `Done` saat director approve.
3. Badge/checklist `Reservation Approval (Non-Creator)` berubah `Done` hanya jika reservation approver bukan creator quotation.
4. Bagian `Waiting for` hilang saat semua syarat approval terpenuhi.

## Notes
1. `approved_by` pada quotation mengikuti approver role Director saat syarat lengkap terpenuhi.
2. Invoice generation tetap berjalan ketika status benar-benar berubah ke `approved`.
3. Jika status diubah ke `pending` oleh Director, approval harus dilakukan ulang dari awal.

