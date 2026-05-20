# VOYEX CRM Blueprint Master

Last Updated: 2026-05-21  
Owner: Engineering/Product  
Status: Canonical (Source of Truth)

## Canonical Rule
- Dokumen ini adalah blueprint utama sistem VOYEX CRM.
- Jika ada konflik isi dengan dokumen blueprint/teknis lain, dokumen ini yang dipakai.
- Dokumen lain berfungsi sebagai detail implementasi atau histori.

## 1. System Identity
- Sistem: VOYEX CRM (Travel Agent Management System).
- Tujuan: mengelola proses end-to-end dari request awal hingga transaksi dan operasional selesai secara terkontrol.
- Prinsip:
  - tidak rebuild dari nol,
  - alignment terhadap sistem existing,
  - perubahan aman terhadap data berjalan.

## 2. Canonical End-to-End Workflow
```text
Customer / Agent
-> Inquiry
-> Itinerary
-> Quotation
-> Quotation Validation
-> Booking
-> Invoice
-> Payment
-> Operation
-> Dispatch / SPK
-> Adjustment
-> Settlement
-> Closed
```

## 3. Module Contracts (Input -> Output)
| Module | Input Utama | Output Utama |
|---|---|---|
| Customer / Agent | identity, contact, type, source | customer profile |
| Inquiry | customer, request detail, deadline, owner | qualified or terminal inquiry |
| Itinerary | inquiry context, day-item plan | itinerary option |
| Quotation | inquiry/itinerary snapshot, pricing | quotation version |
| Quotation Validation | contract rate, markup, supplier check | validated quotation items |
| Booking | accepted quotation | booking header + booking items snapshot |
| Invoice | booking + financial event | billable invoice lifecycle |
| Payment | invoice + payment proof | confirmed/rejected/cancelled payment state |
| Operation | ready booking + service execution | operation progression |
| Dispatch/SPK | booking items operation data | SPK + dispatch status |
| Adjustment | post-booking change request | approved/rejected/applied amendment |
| Settlement | invoice/payment/adjustment/service reconciliation | close gate decision |

## 4. Status & Lifecycle Rules (Canonical)
- Source status canonical: `config/statuses.php`.
- UI status label wajib melalui translator/helper (status code tidak diubah per bahasa).
- Core rule ringkas:
  - Inquiry final: `converted_to_booking/lost/cancelled/expired/unqualified`.
  - Quotation locked: `accepted/converted` (perubahan via revision).
  - Booking close hanya lewat settlement gate.
  - Payment yang dihitung ke invoice hanya status `confirmed`.
  - Adjustment `applied` tidak boleh diedit ulang.

## 5. Critical Guards (Must Never Break)
1. Booking hanya dibuat dari quotation eligible sesuai rule aktif.
2. Quotation approval/finalization harus melewati validation gate.
3. Invoice paid/overpaid/void/cancelled tidak boleh diubah unsafe.
4. Payment rejected/cancelled tidak boleh memutasi paid amount invoice.
5. Booking tidak boleh closed jika settlement blockers masih ada.
6. Permission middleware wajib mengontrol route mutation.

## 6. Role & Permission Model
- Framework ACL: Spatie Permission + middleware permission/module.
- Super Admin:
  - dipakai untuk developer/testing,
  - tidak untuk user bisnis operasional.
- Role boundary prinsip:
  - Reservation fokus inquiry/itinerary/quotation/booking operation.
  - Finance/Accountant fokus invoice/payment/settlement lane.
  - Director/Manager pada approval/oversight sesuai permission map.

## 7. UI/UX Alignment Rules
1. Jangan tampilkan tombol jika backend pasti menolak.
2. Tampilkan next recommended action berdasarkan status.
3. Tampilkan blocker reason jika action unavailable.
4. Status badge harus konsisten lintas modul.
5. Detail page harus berperan sebagai workspace operasional.
6. Ikuti `docs/core/LAYOUT_GUIDE.md` untuk pattern layout.

## 8. Data & Migration Safety Rules
1. Hindari ubah migration lama pada project yang sudah pernah deploy.
2. Semua perubahan skema pakai migration baru non-destruktif sebisa mungkin.
3. Perubahan flow kritikal dibungkus transaction.
4. Test lane tidak boleh memakai DB produksi/utama.

## 9. Operational Readiness Rules
- Source of truth go-live:
  - `docs/technical/PHASE_12_GO_LIVE_MASTER.md`
  - `docs/technical/GO_LIVE_EXECUTION_PLAYBOOK.md`
- Go-live hanya boleh jika:
  1. MySQL lane PASS evidence lengkap,
  2. multi-role UAT PASS evidence lengkap,
  3. deploy + rollback drill PASS evidence lengkap.

## 10. Reality Check (Blueprint vs Running Project)
| Area | Current Reality | Source |
|---|---|---|
| Status standardization | sudah disatukan dalam config status | `config/statuses.php`, `docs/technical/TECHNICAL_FIX_NOTES.md` |
| Finance lifecycle | invoice+payment lifecycle sudah berjalan | `docs/technical/PHASE_8_FINANCE_LIFECYCLE_MASTER.md` |
| Operation lifecycle | operation+dispatch+SPK sudah berjalan | `docs/technical/PHASE_9_OPERATION_MASTER.md` |
| Go-live readiness | masih bergantung evidence staging execution | `docs/technical/PHASE_12_GO_LIVE_MASTER.md` |

## 11. New Developer/AI Onboarding (10-Minute Path)
1. Baca dokumen ini sampai selesai.
2. Baca `PROJECT_GUIDELINES.md`.
3. Baca `PROJECT_KNOWLEDGE_BASE.md`.
4. Baca `docs/README.md`.
5. Baca `docs/technical/TECHNICAL_FIX_NOTES.md` (entry terbaru dulu).
6. Baca modul yang akan disentuh:
   - itinerary/quotation/booking/invoice/payment/operation sesuai task.

## 12. Definition of Done for Any Feature/Fix
1. Business rule tidak regress.
2. Permission/action guard konsisten UI+backend.
3. Status transition valid.
4. Activity log kritikal tetap tercatat.
5. Dokumentasi canonical diupdate (minimal dokumen ini + technical notes jika perlu).

## 13. Deferred / Known Constraints
1. Sebagian execution evidence go-live masih tergantung staging/CI environment readiness.
2. Beberapa enhancement non-blocking tetap ditunda ke fase lanjutan (sesuai phase master terkait).

## 14. Change Log (Blueprint Master)
- 2026-05-21: Initial canonical master blueprint consolidated from blueprint package + technical phase masters.
