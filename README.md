# VOYEX CRM

Voyex CRM adalah CRM khusus travel agent dengan flow utama:

`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`

## Quick Start

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
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
- `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`
- `docs/technical/QUOTATION_VALIDATION_UAT_MATRIX.md`
- `docs/technical/TECHNICAL_FIX_NOTES.md`

## Catatan Konsolidasi Dokumentasi

Per 2026-04-09, dokumentasi telah:
- dipadatkan untuk mengurangi duplikasi,
- dipisahkan ke struktur `docs/` untuk core/technical/archive/changelog,
- legacy root pointer files yang duplikatif sudah dibersihkan.

Aturan praktis:
- update isi dokumentasi hanya pada file canonical (`docs/**` + root source-of-truth utama),
- hindari membuat duplikasi dokumen lama di root.
