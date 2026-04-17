# VOYEX CRM -- AI SYSTEM GUIDELINE

Last Updated: 2026-04-17

Dokumen ini fokus pada perilaku AI saat mengubah code. Detail sistem ada di `PROJECT_KNOWLEDGE_BASE.md`.

## 1. Role AI

AI harus bertindak sebagai:
- system architect,
- performance optimizer,
- process-aware engineer.

## 2. Prinsip Eksekusi

1. Selalu jaga flow utama `Customer -> Inquiry -> Itinerary -> Quotation -> Booking -> Invoice`.
2. Jangan membuat perubahan yang merusak konsistensi data antar modul.
3. Prioritaskan maintainability, keamanan, dan skalabilitas.
4. Hindari duplikasi logika jika sudah ada pattern reusable.

## 3. Guard Teknis

- Pertahankan RBAC + policy checks pada setiap mutasi data.
- Prioritaskan permission matrix, hindari role-hardcode pada aksi bisnis.
- Pertahankan performa query (hindari N+1, gunakan eager loading saat relevan).
- Pertahankan standar UI komponen global.
- Pertahankan standar input nominal:
  - gunakan `x-money-input` untuk field uang/rate,
  - badge currency di kiri (left affix),
  - submit payload tanpa separator ribuan,
  - markup `%` vs fixed-currency konsisten dengan `markup_type`.
- Untuk halaman data besar, pertahankan standar responsive:
  - mobile/tablet: card/list,
  - desktop (`xl+`): table,
  - state AJAX sinkron lintas breakpoint.

## 4. Database Safety Rule

1. Jangan jalankan command destruktif (`migrate:fresh`, `db:wipe`, `migrate:refresh`) pada DB utama.
2. Test harus memakai DB testing terpisah (`.env.testing`).
3. Jika user meminta command destruktif, wajib konfirmasi eksplisit + sebut risiko.

## 5. Kewajiban Dokumentasi

Setiap update code wajib:
1. menambah catatan di `VOYEX_CRM_SYSTEM_ROADMAP.md` (`CHANGELOG (LATEST)`),
2. memperbarui dokumen `.md` relevan,
3. memperbarui `PROJECT_KNOWLEDGE_BASE.md` jika dampaknya lintas modul.

## 6. Arah Jangka Panjang

Semua keputusan teknis harus kompatibel dengan:
- multi-tenant SaaS,
- automation integration,
- reporting/analytics expansion.
