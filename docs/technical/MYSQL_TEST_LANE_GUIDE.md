# MySQL Test Lane Guide

Date: 2026-05-21

## Purpose

Panduan menjalankan integration/feature test finance & settlement pada lane MySQL (bukan sqlite).

## Required Database

Buat database:

```sql
CREATE DATABASE voyex_crm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Default yang dipakai `phpunit.mysql.xml.example`:
- DB_CONNECTION=mysql
- DB_HOST=127.0.0.1
- DB_PORT=3306
- DB_DATABASE=voyex_crm_test
- DB_USERNAME=root
- DB_PASSWORD=

Silakan sesuaikan username/password pada server CI/staging bila berbeda.

## Local/Staging Commands

1. `composer install`
2. `php artisan optimize:clear`
3. `php artisan test --configuration=phpunit.mysql.xml.example`

## If Composer/Test Tooling Missing

Jika sebelumnya dijalankan `composer install --no-dev`, command `php artisan test` bisa hilang.
Pemulihan:
1. Jalankan ulang `composer install` (dengan dev dependency).
2. Pastikan `vendor/bin/phpunit` tersedia.
3. Ulangi command test MySQL lane.

## CI Example

```bash
cp phpunit.mysql.xml.example phpunit.mysql.xml
php artisan optimize:clear
php artisan test --configuration=phpunit.mysql.xml
```

## Expected Outcome

- Semua test finance/settlement berjalan pada MySQL lane.
- Bila ada skip SQLite guard, itu expected selama lane ini benar-benar MySQL.

## Current Phase 12C Status

- Environment ini: `BLOCKED` (dev dependency install terkendala permission/cache write).
- Action: jalankan lane ini di staging/CI runner yang memiliki akses penuh file system + MySQL service.
