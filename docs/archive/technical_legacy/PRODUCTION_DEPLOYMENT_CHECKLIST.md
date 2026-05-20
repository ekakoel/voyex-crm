# Production Deployment Checklist

Date: 2026-05-21

## Pre-Deploy

1. Backup database production/staging.
2. Verify `.env` production values:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_LEVEL=error` atau `warning`
3. Ensure secrets are not committed.

## Build & Release

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci`
3. `npm run build`
4. `php artisan migrate --force`
5. `php artisan storage:link`

## Cache & Optimize

1. `php artisan config:cache`
2. `php artisan route:cache`
3. `php artisan view:cache`

## Runtime Services

1. Restart queue workers if queue is used:
- `php artisan queue:restart`
2. Ensure scheduler cron is active:
- `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

## Security & Access Validation

1. Run `php artisan route:list`.
2. Verify no custom debug route is exposed in production context.
3. Verify role-permission seeder has been applied:
- `php artisan db:seed --class=PermissionSeeder`
- `php artisan db:seed --class=RolePermissionSeeder`

## Post-Deploy Smoke Checks

1. Login with Reservation role:
- Booking, Operation, Settlement view path works.
2. Login with Finance role:
- Invoice/Payment/Settlement review path works.
3. Login with Director role:
- Can close booking only via settlement gate.
4. Validate payment proof file access and SPK print access permissions.

## Monitoring

1. Check application logs for new errors:
- monitor `storage/logs/laravel.log`.
2. Track HTTP 500 spikes in first 24 hours.
3. Validate scheduled jobs run successfully:
- inquiries reminder,
- hotel status sync,
- currency sync.
