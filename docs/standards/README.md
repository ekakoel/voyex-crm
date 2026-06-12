# Standards Index

Last Updated: 2026-05-25

Dokumen ini adalah pintu masuk standar implementasi modul agar tim dev memiliki referensi tunggal sebelum melakukan perubahan fitur.

## Available Standards
- Inquiry Module: [inquiry-standard.md](/D:/Eka Koel/App/2026/crm-balikamitour/docs/standards/inquiry-standard.md)
- Quotation Module: [quotation-standard.md](/D:/Eka Koel/App/2026/crm-balikamitour/docs/standards/quotation-standard.md)
- Map & Location Standard: [map-location-standard.md](/D:/Eka Koel/App/2026/crm-balikamitour/docs/standards/map-location-standard.md)

## How To Use
1. Baca standar modul terkait sebelum coding.
2. Pastikan validasi backend, aturan status, dan relasi data mengikuti standar.
3. Jika ada perubahan aturan bisnis:
   - update kode,
   - update dokumen standar modul terkait,
   - update changelog pada `docs/blueprint/VOYEX_STATUS_MATRIX.md`.

## Implementation Checklist
- Relasi data sesuai standar (contoh: Inquiry 1:1 Quotation).
- Filter data di form sesuai ownership & lifecycle status.
- Field wajib/opsional konsisten antara UI dan backend validation.
- State/status terminal tidak bisa diproses ke flow aktif lagi.
- Perubahan UX form tetap menjaga kompatibilitas data existing.

## Notes
- Jika ada konflik antara implementasi lama dan standar ini, ikuti standar terbaru lalu buat migration/compatibility patch yang aman.
- Hindari perubahan parsial tanpa update dokumentasi agar tidak memunculkan regresi lintas modul.
