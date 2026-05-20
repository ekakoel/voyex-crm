# VOYEX CRM Blueprint Package

Paket dokumen ini dibuat untuk membantu audit dan penyelarasan project Laravel VOYEX CRM yang sudah berjalan.

## Cara Pakai

1. Upload folder/dokumen ini ke repository, disarankan ke:
   - `docs/blueprint/`
2. Jalankan Codex menggunakan file:
   - `05_CODEX_MASTER_PROMPT.md`
   - lalu `06_CODEX_AUDIT_PROMPT.md`
3. Setelah audit selesai, gunakan:
   - `04_ROADMAP_CHECKLIST.md`
   - `07_MODULE_IMPLEMENTATION_CHECKLIST.md`
   - `08_ACCEPTANCE_TEST_UAT.md`
4. Jangan minta Codex rebuild dari nol. Minta Codex audit, align, fix, dan complete berdasarkan blueprint.

## Isi File

| File | Fungsi |
|---|---|
| `01_BLUEPRINT_VOYEX_CRM.md` | Blueprint utama sistem travel agent |
| `02_BUSINESS_RULES_STATUS_FLOW.md` | Aturan bisnis dan status lifecycle |
| `03_REPOSITORY_AUDIT_FINDINGS.md` | Temuan awal dari repository GitHub |
| `04_ROADMAP_CHECKLIST.md` | Roadmap pengerjaan bertahap |
| `05_CODEX_MASTER_PROMPT.md` | Prompt utama untuk Codex |
| `06_CODEX_AUDIT_PROMPT.md` | Prompt audit awal untuk Codex |
| `07_MODULE_IMPLEMENTATION_CHECKLIST.md` | Checklist implementasi per modul |
| `08_ACCEPTANCE_TEST_UAT.md` | UAT dan definisi project siap digunakan |
| `09_MIGRATION_AND_REFACTOR_GUIDE.md` | Panduan aman membuat migration/refactor |
| `VOYEX_CRM_PROJECT_CHECKLIST.csv` | Checklist CSV untuk tracking |

## Prinsip Utama

VOYEX CRM tidak perlu dibuat ulang dari awal. Sistem perlu diselaraskan agar business flow benar:

```text
Request Source
→ Customer / Agent
→ Inquiry
→ Itinerary
→ Quotation
→ Quotation Validation
→ Negotiation / Revision
→ Booking
→ Invoice
→ Payment
→ Operation / Service Date
→ Adjustment
→ Settlement
→ Closed
```
