# Voyex CRM Project Guidelines

Dokumen ini berisi aturan kerja wajib. Untuk detail domain/fitur, rujuk `PROJECT_KNOWLEDGE_BASE.md`.

## 1. Mandatory Execution Protocol (Wajib)

1. Pahami penuh flow/code area yang akan diubah sebelum implementasi.
2. Solusi wajib aman dari regression, menjaga performa, dan user-friendly.
3. Perubahan wajib lulus validasi end-to-end pada flow terkait.
4. Jika perubahan lintas modul, sinkronkan dokumentasi lintas dokumen utama.

## 2. Prioritas Dokumen (Jika Terjadi Konflik)

1. `PROJECT_GUIDELINES.md`
2. `PROJECT_KNOWLEDGE_BASE.md`
3. `VOYEX_CRM_SYSTEM_ROADMAP.md`
4. `docs/core/LAYOUT_GUIDE.md`
5. Dokumen teknis spesifik modul

## 3. Scope Sistem

Flow bisnis inti:
`Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`

Status standar lintas modul transaksi:
- `draft`
- `processed`
- `pending`
- `approved`
- `rejected`
- `final`

Aturan: jika `final`, data view-only (tanpa mutasi).

## 4. Aturan Implementasi Umum

- Gunakan validasi request/controller yang ketat dan eksplisit.
- Gunakan eager loading + pagination untuk list besar.
- Jaga konsistensi policy/permission saat menambah aksi baru.
- Hindari duplikasi data yang tidak perlu (single source of truth per konteks).
- Ikuti standar UI global (`app-card`, `app-table`, `app-input`, `btn-*`).

## 5. Aturan Dokumentasi Wajib

1. Setiap perubahan code wajib dicatat di `VOYEX_CRM_SYSTEM_ROADMAP.md` bagian `CHANGELOG (LATEST)`.
2. Setiap perubahan code wajib mengupdate minimal satu dokumen `.md` yang relevan.
3. Perubahan lintas modul/arsitektur wajib mengupdate `PROJECT_KNOWLEDGE_BASE.md`.

## 6. QA Minimum Setelah Perubahan

1. Cek visual/layout pada halaman yang disentuh.
2. Cek aksi utama (create/edit/list/show) pada flow terkait.
3. Cek empty/error state yang relevan.
4. Pastikan tidak ada error JS console untuk halaman interaktif.
5. Catat ringkas hasil QA pada laporan pekerjaan.

## 7. Referensi Ringkas

- Sistem menyeluruh: `PROJECT_KNOWLEDGE_BASE.md`
- Roadmap + changelog: `VOYEX_CRM_SYSTEM_ROADMAP.md`
- Peta dokumentasi: `docs/README.md`
- Layout: `docs/core/LAYOUT_GUIDE.md`
- Itinerary detail teknis: `docs/technical/ITINERARY_CREATE_EDIT_FLOW.md`, `docs/technical/ITINERARY_DETAIL_MAP_ARCHITECTURE.md`
- UAT quotation approval: `docs/technical/QUOTATION_APPROVAL_UAT_MATRIX.md`
- Ringkasan fix teknis: `docs/technical/TECHNICAL_FIX_NOTES.md`
