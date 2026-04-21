# VOYEX CRM

Last Updated: 2026-04-21

Voyex CRM adalah CRM khusus travel agent dengan flow utama:

`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`

## Quick Start

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

## Environment Safety (Wajib)

1. Jangan jalankan command destruktif (`migrate:fresh`, `db:wipe`) pada database produksi/utama.
2. Test suite (`php artisan test`) wajib memakai database testing terpisah.
3. Buat `.env.testing` khusus, jangan biarkan testing memakai DB yang sama dengan `.env`.
4. Sebelum command migration besar, lakukan backup database terlebih dahulu.

## Date Format Guard (CI)

Standar wajib tampilan tanggal/waktu:
- tanggal: `YYYY-MM-DD`
- tanggal+waktu: `YYYY-MM-DD (HH:ii)`

CI guard untuk rule ini:
- workflow: `.github/workflows/date-format-guard.yml`
- script: `scripts/ci/check-date-format.sh`

Jalankan lokal sebelum push:

```bash
bash scripts/ci/check-date-format.sh
```

Untuk Windows PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/ci/check-date-format.ps1
```

## Deploy Permission Baseline

Untuk menyamakan permission lintas environment (tanpa reset DB), jalankan:

```bash
php artisan db:seed --class=PermissionBaselineSeeder --force
```

Seeder ini menjadi baseline tunggal untuk:
- pembuatan seluruh permission modul/global,
- sinkronisasi mapping role-permission default.

## Deploy Baseline Project (Seeder Tunggal)

Untuk mengurangi kebingungan karena seeder terpisah, gunakan satu entrypoint:

```bash
php artisan db:seed --class=ProjectBaselineSeeder --force
```

Untuk deploy server (migrate + seed baseline):

```bash
php artisan migrate --force
php artisan db:seed --class=ProjectBaselineSeeder --force
```

## Dokumentasi Utama (Source of Truth)

1. `PROJECT_KNOWLEDGE_BASE.md` -> gambaran sistem menyeluruh (domain, modul, relasi, standar).
2. `PROJECT_GUIDELINES.md` -> protokol kerja wajib dan aturan implementasi.
3. `VOYEX_CRM_SYSTEM_ROADMAP.md` -> roadmap status + changelog terkini.
4. `docs/core/LAYOUT_GUIDE.md` -> standar layout dan pola UI lintas modul.
5. `docs/README.md` -> peta struktur dokumentasi terbaru.

## Dokumentasi Teknis Spesifik

- `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`
- `docs/technical/ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
- `docs/technical/ISLAND_TRANSFER_MODULE.md`
- `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`
- `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md`
- `docs/technical/NOMINAL_INPUT_STANDARD.md`
- `docs/technical/I18N_TRANSLATION_STANDARD.md`
- `docs/technical/IMAGE_THUMBNAIL_STANDARD.md`
- `docs/technical/TECHNICAL_FIX_NOTES.md`
- `docs/technical/PERFORMANCE_OPTIMIZATION_STANDARD.md`

## Catatan Konsolidasi Dokumentasi

Per 2026-04-09, dokumentasi telah:
- dipadatkan untuk mengurangi duplikasi,
- dipisahkan ke struktur `docs/` untuk core/technical/archive/changelog,
- legacy root pointer files yang duplikatif sudah dibersihkan.

Aturan praktis:
- update isi dokumentasi hanya pada file canonical (`docs/**` + root source-of-truth utama),
- hindari membuat duplikasi dokumen lama di root.
