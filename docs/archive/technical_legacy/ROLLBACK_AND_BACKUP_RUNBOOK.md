# Rollback and Backup Runbook

Date: 2026-05-21

## Objective

Menjamin deployment bisa dipulihkan dengan cepat jika terjadi insiden pasca-release.

## A. Pre-Deploy Backup

1. Masuk maintenance mode:
- `php artisan down`

2. Backup database (contoh MySQL):
```bash
mysqldump -u <db_user> -p <db_name> > backup_<date>_<time>.sql
```

3. Backup storage penting:
```bash
tar -czf storage_backup_<date>_<time>.tar.gz storage/app storage/logs public/storage
```

4. Simpan backup ke lokasi aman (remote bucket/server backup).

## B. Deploy Steps (ringkas)

1. `git pull origin main`
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build`
4. `php artisan migrate --force`
5. `php artisan storage:link`
6. `php artisan config:cache`
7. `php artisan route:cache`
8. `php artisan view:cache`
9. `php artisan up`

## C. Emergency Rollback

1. Aktifkan maintenance mode:
- `php artisan down`

2. Kembali ke commit stabil sebelumnya:
```bash
git log --oneline -n 10
git checkout <stable_commit_sha>
```

3. Restore database:
```bash
mysql -u <db_user> -p <db_name> < backup_<date>_<time>.sql
```

4. Restore storage bila dibutuhkan:
```bash
tar -xzf storage_backup_<date>_<time>.tar.gz
```

5. Bersihkan cache Laravel:
- `php artisan optimize:clear`

6. Aktifkan kembali aplikasi:
- `php artisan up`

## D. Post-Rollback Validation

1. Login admin berhasil.
2. Dashboard tampil normal.
3. Core route booking/invoice/payment/settlement tidak error.
4. Cek `storage/logs/laravel.log` untuk error baru.

## E. Drill Requirement

Lakukan minimal 1 rollback drill di staging sebelum go-live final, lalu simpan evidence waktu + operator + hasil.
