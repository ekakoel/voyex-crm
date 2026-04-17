# Quotation Approval UAT Matrix

Last Updated: 2026-04-17

Dokumen ini untuk validasi flow approval quotation yang aktif saat ini.

## Current Rule (Source of Truth)

1. Approval actor harus punya permission `quotations.approve`.
2. Creator quotation tidak boleh approve quotation miliknya sendiri.
3. Minimal 2 approval valid dari non-creator diperlukan agar status berubah ke `approved`.
4. Approval role label diturunkan dari dashboard permission approver:
   - `dashboard.director.view` -> director
   - `dashboard.manager.view` -> manager
   - `dashboard.reservation.view` -> reservation
5. Jika validation required dan belum complete (`validation_status != valid`), approval harus ditolak.

## Preconditions

1. Jalankan migration terbaru:
   - `php artisan migrate`
2. Sync permission role terbaru:
   - `php artisan db:seed --class=PermissionSeeder`
   - `php artisan db:seed --class=RolePermissionSeeder`
3. Siapkan minimal user:
   - creator quotation (contoh Marketing),
   - minimal dua approver non-creator yang punya `quotations.approve`.

## Scenario Matrix

| No | Actor | Action | Expected Result |
|---|---|---|---|
| 1 | Creator | Create quotation | Quotation tersimpan status `pending` |
| 2 | Creator | Klik `Approve` | Ditolak, creator tidak bisa approve quotation sendiri |
| 3 | Approver A (non-creator) | Klik `Approve` | Approval tercatat, status masih `pending` |
| 4 | Approver B (non-creator) | Klik `Approve` | Approval tercatat, status berubah `approved` (karena quota 2 tercapai) |
| 5 | Actor tanpa `quotations.approve` | Akses approve route | Ditolak (`403` / error permission) |
| 6 | Approver | Approve quotation yang butuh validation tapi belum `valid` | Ditolak dengan pesan validation guard |
| 7 | User dengan `quotations.reject` | Klik `Reject` | Status `rejected`, approval log dibersihkan |
| 8 | User dengan `quotations.set_pending` | Klik `Set Pending` dari status `approved` | Status kembali `pending`, approval log di-reset |
| 9 | User dengan `quotations.set_final` | Klik `Set Final` pada quotation `approved` | Status berubah `final` |
| 10 | User tanpa `quotations.set_final` | Klik `Set Final` | Ditolak (`403` / error permission) |

## Fast Execution Order (Recommended)

1. Login creator, buat quotation baru.
2. Login approver A, approve.
3. Login approver B, approve.
4. Verifikasi status akhir `approved`.
5. Uji `set_pending`, `reject`, dan `set_final` sesuai permission.

## Negative Test (Recommended)

1. Coba approve dari creator quotation.
2. Coba approve dari user tanpa `quotations.approve`.
3. Coba approve saat `validation_status` belum `valid` (untuk quotation yang membutuhkan validasi).
4. Coba `set_final` dari user tanpa permission `quotations.set_final`.

## Notes

1. Approval guard sekarang permission-first, bukan role-hardcode.
2. Quorum approval aktif saat ini adalah minimal 2 approval non-creator.
3. Status `final` adalah lock-state, tidak boleh dimutasi kembali.
