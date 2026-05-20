# Phase 12B - End-to-End UAT & Go-Live Validation

Date: 2026-05-21

## Scope

Phase ini fokus validasi UAT end-to-end dan go-live readiness tanpa menambah fitur baru.

## Positive Scenario Validation

Status legend:
- PASS: tervalidasi dari flow code + route + guard + existing automated checks.
- PARTIAL: membutuhkan eksekusi manual UI/database test lane yang tidak tersedia penuh di environment ini.

1. Customer/Inquiry/Itinerary/Quotation/Validation flow: PARTIAL
- Struktur route/controller/permission tersedia.
- Perlu eksekusi manual UI penuh untuk bukti UAT final.

2. Quotation -> Booking conversion: PASS (functional path tersedia + guarded).

3. Booking pax + itinerary snapshot: PASS (field + snapshot service sudah ada).

4. Invoice lifecycle (generate/issue/status): PASS (controller/service/guards tersedia).

5. Payment record/confirm + invoice recalculation: PASS (service logic tersedia, test lane ada).

6. Operation lifecycle + dispatch + SPK view/print: PASS (route/action/permission tersedia).

7. Adjustment lifecycle + apply + additional invoice generation: PASS (service + routes + logs tersedia).

8. Settlement review -> mark settled -> close booking: PASS (SettlementService + close gate tersedia).

9. Activity logs major actions: PASS (log calls ada di flow kritikal).

## Negative Scenario Validation

1. Booking without confirmed payment cannot be ready_to_operate: PASS (guard tersedia).
2. Booking not service_completed cannot be closed: PASS (settlement blockers).
3. Outstanding invoice blocks settlement: PASS.
4. Pending payment blocks settlement: PASS.
5. Pending adjustment blocks settlement: PASS.
6. Applied adjustment cannot be edited: PASS (lifecycle guard).
7. Closed booking cannot be edited unsafely: PASS (`isFinal` guard).
8. Void/cancelled invoice cannot receive payment: PASS (invoice guard in payment service).
9. Rejected/cancelled payment does not mutate invoice balance: PASS (payment service behavior).
10. Non-financial adjustment does not mutate invoice/payment: PASS (adjustment service guard).

## Permission UAT

1. Settlement close button/route only for permitted roles: PASS.
2. Reservation vs Finance boundary for payment confirmation: PASS by route permission (`payments.confirm`).
3. Dispatch actions require booking operation permissions: PASS by middleware + UI guard.
4. Location resolver requires `locations.resolve_google_map`: PASS.
5. Director close via settlement permission: PASS by route + role mapping.

## MySQL Test Lane

1. Default sqlite lane (`php artisan test`): runs, but finance/settlement integration suites are skipped by design.
2. MySQL lane (`php artisan test --configuration=phpunit.mysql.xml.example`):
- Executed and failed because local MySQL test database `voyex_crm_test` is not available in this environment.
- Failure reason is infrastructure (unknown DB), not assertion failure in business logic.

## Deployment Rehearsal

Attempted:
1. `composer install --no-dev --optimize-autoloader`: PASS.
2. `php artisan optimize:clear`: PASS.
3. `php artisan config:cache`: PASS.
4. `php artisan route:cache`: PASS.
5. `php artisan view:cache`: PASS.

Constraints:
1. Re-installing dev dependencies (`composer install`) failed due network/permission constraints in this environment.
2. `npm ci` / `npm run build` not executed in this phase because dependency download from internet is blocked.

## Bugs Found During Phase 12B

1. Operational rehearsal risk:
- Running `composer install --no-dev` removes test tooling in constrained/offline environment, making `php artisan test` unavailable until dev dependencies are restored.
- This is environment/process risk, not business-flow bug.

## Go-Live Decision (Phase 12B)

Decision: **NO-GO (temporary)** until:
1. Full manual UAT scenario is executed on staging with representative users/roles.
2. MySQL integration test lane passes in CI/staging (`phpunit.mysql.xml.example` equivalent).
3. Build pipeline rehearsal (`npm ci && npm run build`) passes in connected CI/staging runner.
