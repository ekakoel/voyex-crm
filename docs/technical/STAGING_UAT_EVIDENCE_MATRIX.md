# Staging UAT Evidence Matrix

Date: 2026-05-21

Gunakan dokumen ini sebagai bukti eksekusi manual staging.
Isi kolom user, timestamp, dan lampiran screenshot/log saat dijalankan di staging.

## A. Multi-Role Access Matrix

| Role | Tested User | Actions Tested | Expected | Result | Evidence |
|---|---|---|---|---|---|
| Reservation | N/A (staging pending) | inquiry/itinerary/quotation/booking operation/dispatch/SPK | allowed | BLOCKED | pending staging run |
| Reservation | N/A | confirm/reject payment, close booking | denied | BLOCKED | pending staging run |
| Finance | N/A | invoice/payment/settlement review | allowed | BLOCKED | pending staging run |
| Finance | N/A | dispatch operation | denied by default | BLOCKED | pending staging run |
| Accountant | N/A | view/review finance | allowed | BLOCKED | pending staging run |
| Accountant | N/A | unauthorized operation actions | denied | BLOCKED | pending staging run |
| Manager | N/A | review/approve permitted actions | allowed | BLOCKED | pending staging run |
| Director | N/A | mark settled / close booking | allowed if permitted | BLOCKED | pending staging run |
| Administrator | N/A | full management access | allowed | BLOCKED | pending staging run |
| Super Admin | N/A | full management access | allowed | BLOCKED | pending staging run |

## B. Positive Workflow Matrix (27 Steps)

| # | Step | Result | Evidence |
|---|---|---|---|
| 1 | Create customer/agent | BLOCKED | pending staging run |
| 2 | Create inquiry | BLOCKED | pending staging run |
| 3 | Assign inquiry | BLOCKED | pending staging run |
| 4 | Create itinerary | BLOCKED | pending staging run |
| 5 | Generate quotation | BLOCKED | pending staging run |
| 6 | Validate quotation | BLOCKED | pending staging run |
| 7 | Accept/finalize quotation | BLOCKED | pending staging run |
| 8 | Convert to booking | BLOCKED | pending staging run |
| 9 | Generate invoice | BLOCKED | pending staging run |
| 10 | Issue invoice | BLOCKED | pending staging run |
| 11 | Record payment | BLOCKED | pending staging run |
| 12 | Confirm payment | BLOCKED | pending staging run |
| 13 | Mark ready_to_operate | BLOCKED | pending staging run |
| 14 | Start operation | BLOCKED | pending staging run |
| 15 | Confirm vendor | BLOCKED | pending staging run |
| 16 | Assign driver/guide text | BLOCKED | pending staging run |
| 17 | View/print SPK | BLOCKED | pending staging run |
| 18 | Complete service | BLOCKED | pending staging run |
| 19 | Create additional_service adjustment | BLOCKED | pending staging run |
| 20 | Submit adjustment | BLOCKED | pending staging run |
| 21 | Approve adjustment | BLOCKED | pending staging run |
| 22 | Apply adjustment | BLOCKED | pending staging run |
| 23 | Confirm additional invoice created | BLOCKED | pending staging run |
| 24 | Record + confirm additional payment | BLOCKED | pending staging run |
| 25 | Review settlement | BLOCKED | pending staging run |
| 26 | Mark settlement settled | BLOCKED | pending staging run |
| 27 | Close booking | BLOCKED | pending staging run |

## C. Negative Workflow Matrix

| Case | Expected | Result | Evidence |
|---|---|---|---|
| unpaid booking cannot become ready_to_operate | blocked | BLOCKED | pending staging run |
| booking not service_completed cannot close | blocked | BLOCKED | pending staging run |
| outstanding invoice blocks settlement | blocked | BLOCKED | pending staging run |
| pending payment blocks settlement | blocked | BLOCKED | pending staging run |
| pending adjustment blocks settlement | blocked | BLOCKED | pending staging run |
| applied adjustment cannot be edited | blocked | BLOCKED | pending staging run |
| closed booking cannot be edited unsafely | blocked | BLOCKED | pending staging run |
| void/cancelled invoice cannot receive payment | blocked | BLOCKED | pending staging run |
| rejected/cancelled payment ignored in paid amount | ignored | BLOCKED | pending staging run |
| non-financial adjustment does not mutate invoice/payment | no mutation | BLOCKED | pending staging run |

## D. MySQL Lane Evidence

| Command | Result | Notes |
|---|---|---|
| composer install | BLOCKED | permission denied on cache/vendor temp in this environment |
| php artisan optimize:clear | DONE | executed successfully |
| php artisan test --configuration=phpunit.mysql.xml.example | BLOCKED | `test` command unavailable (dev dependency not installed) |

## E. Deployment Evidence

| Command | Result | Notes |
|---|---|---|
| php artisan queue:restart | DONE | restart signal broadcasted |
| php artisan route:list | DONE | route output rendered |
| php artisan about | DONE | app boots; env local; debug enabled |
| Full staging deploy command set | BLOCKED/PARTIAL | pending dedicated staging execution |
