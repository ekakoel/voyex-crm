# Voyex CRM - Cheat Sheet Operasional

Ringkasan super cepat. Detail lengkap ada di `PROJECT_KNOWLEDGE_BASE.md`.

## 1. Flow Inti

`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`

## 2. Status Standar

`draft`, `processed`, `pending`, `approved`, `rejected`, `final`

Jika `final`: view-only.

## 3. Guard Akses

- middleware: `module:*`, `permission:*`, `module.permission:*`
- policy penting:
  - Inquiry update: creator atau assigned user
  - Itinerary/Quotation/Booking mutasi: creator (dengan override sesuai policy/project)

## 4. Aturan UI

- table: `app-card` + `app-table`
- input: `app-input`
- tombol: `btn-*`
- index standar: summary stats + filter kiri + list kanan

## 5. Dokumen yang Wajib Dibuka Saat Onboarding

1. `PROJECT_KNOWLEDGE_BASE.md`
2. `PROJECT_GUIDELINES.md`
3. `VOYEX_CRM_SYSTEM_ROADMAP.md`
4. `docs/core/LAYOUT_GUIDE.md`
5. `docs/README.md`
