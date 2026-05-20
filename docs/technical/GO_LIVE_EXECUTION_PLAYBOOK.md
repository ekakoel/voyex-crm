# Go-Live Execution Playbook

Last Updated: 2026-05-21

## 1. Purpose
- Menjadi dokumen eksekusi tunggal untuk:
  - UAT go-live checklist,
  - deployment checklist,
  - backup/rollback runbook,
  - MySQL test lane guide,
  - staging evidence matrix.

## 2. End-to-End UAT Checklist

### A. Positive Workflow
- [ ] Create customer/agent
- [ ] Create inquiry
- [ ] Assign inquiry to reservation
- [ ] Create itinerary from inquiry
- [ ] Generate quotation from itinerary
- [ ] Validate quotation
- [ ] Accept/finalize quotation
- [ ] Convert quotation to booking
- [ ] Verify pax + itinerary snapshot
- [ ] Generate invoice
- [ ] Issue invoice
- [ ] Record payment
- [ ] Confirm payment
- [ ] Verify invoice status transition
- [ ] Mark booking ready_to_operate
- [ ] Start operation
- [ ] Confirm vendor/service item
- [ ] Fill driver/guide assignment
- [ ] View/print SPK
- [ ] Complete service
- [ ] Create adjustment additional_service
- [ ] Submit adjustment
- [ ] Approve adjustment
- [ ] Apply adjustment
- [ ] Verify additional invoice generated
- [ ] Record + confirm additional payment
- [ ] Review settlement
- [ ] Mark settlement settled
- [ ] Close booking
- [ ] Verify booking status = closed
- [ ] Verify activity logs critical actions

### B. Negative Workflow
- [ ] Without confirmed payment booking cannot `ready_to_operate`
- [ ] Without `service_completed` booking cannot close
- [ ] Outstanding invoice blocks settlement
- [ ] Pending payment blocks settlement
- [ ] Pending adjustment blocks settlement
- [ ] Applied adjustment cannot be edited
- [ ] Closed booking cannot be edited unsafely
- [ ] Void/cancelled invoice cannot receive payment
- [ ] Rejected/cancelled payment does not change invoice balance
- [ ] Non-financial adjustment does not mutate invoice/payment

### C. Role & Permission
- [ ] Reservation: inquiry/itinerary/quotation/booking operation
- [ ] Finance: invoice/payment/settlement review
- [ ] Accountant: finance view/review per policy
- [ ] Manager: review/approval per policy
- [ ] Director: settlement close booking if permitted
- [ ] Finance has no dispatch action (default)
- [ ] Reservation cannot confirm/reject payment (default)
- [ ] `location/resolve-google-map` requires `locations.resolve_google_map`
- [ ] Settlement close action appears only when state+role valid

## 3. MySQL Test Lane

### Required DB
```sql
CREATE DATABASE voyex_crm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Command Lane
1. `composer install`
2. `php artisan optimize:clear`
3. `php artisan test --configuration=phpunit.mysql.xml.example`

### Notes
- Jika sebelumnya menjalankan `composer install --no-dev`, maka `php artisan test` bisa tidak tersedia.
- Pulihkan dengan `composer install` (dev dependency aktif).

## 4. Deployment Checklist

### Pre-Deploy
1. Backup database.
2. Verify production env:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `LOG_LEVEL=error`/`warning`
3. Ensure secrets are not committed.

### Build & Release
1. `composer install --no-dev --optimize-autoloader`
2. `npm ci`
3. `npm run build`
4. `php artisan migrate --force`
5. `php artisan storage:link`

### Cache & Optimize
1. `php artisan config:cache`
2. `php artisan route:cache`
3. `php artisan view:cache`

### Runtime
1. `php artisan queue:restart`
2. Ensure scheduler cron active.

### Security Validation
1. `php artisan route:list`
2. Verify no debug route exposed in production.
3. Seed baseline permission if required:
   - `php artisan db:seed --class=PermissionSeeder`
   - `php artisan db:seed --class=RolePermissionSeeder`

## 5. Backup & Rollback Runbook

### Pre-Deploy Backup
1. `php artisan down`
2. DB backup (`mysqldump`)
3. Storage backup (`tar`)
4. Store backup off-host

### Emergency Rollback
1. `php artisan down`
2. checkout stable commit
3. restore DB backup
4. restore storage backup
5. `php artisan optimize:clear`
6. `php artisan up`

### Post-Rollback Validation
1. Login/dashboard normal
2. Core booking/invoice/payment/settlement route healthy
3. Log check on `storage/logs/laravel.log`

## 6. Staging Evidence Matrix (Template)

Gunakan format berikut saat eksekusi nyata di staging:

| Area | Item | Result | Evidence |
|---|---|---|---|
| Multi-role | Reservation/Finance/Accountant/Manager/Director/Admin | PASS/BLOCKED | screenshot/log/link |
| Positive flow | 1-27 workflow steps | PASS/BLOCKED | screenshot/log/link |
| Negative flow | 10 negative scenarios | PASS/BLOCKED | screenshot/log/link |
| MySQL lane | composer + optimize + test | PASS/BLOCKED | command output |
| Deploy lane | build/cache/queue/smoke | PASS/BLOCKED | command output |

## 7. Exit Gate
- Go-live can proceed only when:
  1. MySQL lane PASS evidence complete,
  2. multi-role UAT PASS evidence complete,
  3. full deploy + rollback drill PASS evidence complete.
