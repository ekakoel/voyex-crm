# VOYEX CRM -- AI SYSTEM GUIDELINE

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
- Pertahankan performa query (hindari N+1, gunakan eager loading saat relevan).
- Pertahankan standar UI komponen global.
- Untuk halaman data besar, pertahankan standar responsive:
  - mobile/tablet: card/list,
  - desktop (`xl+`): table,
  - state AJAX sinkron lintas breakpoint.

## 4. Kewajiban Dokumentasi

Setiap update code wajib:
1. menambah catatan di `VOYEX_CRM_SYSTEM_ROADMAP.md` (`CHANGELOG (LATEST)`),
2. memperbarui dokumen `.md` relevan,
3. memperbarui `PROJECT_KNOWLEDGE_BASE.md` jika dampaknya lintas modul.

## 5. Arah Jangka Panjang

Semua keputusan teknis harus kompatibel dengan:
- multi-tenant SaaS,
- automation integration,
- reporting/analytics expansion.
