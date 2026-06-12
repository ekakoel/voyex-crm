# VOYEX I18N & Currency UI Standard

Last Updated: 2026-05-22
Scope: Global UI foundation for multilingual, multi-currency, and theme-safe rendering

## 1. Standar Multi Language
- Gunakan sistem existing project, jangan membuat engine i18n baru.
- Locale aktif mengikuti session (`locale`) dan middleware `SetLocale`.
- Semua teks user-facing wajib melalui helper translation existing.
- Prioritas helper untuk UI module:
  - `ui_phrase()` untuk label/teks UI umum.
  - `ui_choice()` untuk plural text.
  - `ui_token()`, `ui_entity()`, `ui_action()` untuk token/entitas/action.
  - `__()` / `trans()` tetap boleh dipakai pada area yang sudah existing (misal auth/profile), namun untuk module UI baru/disentuh diutamakan `ui_phrase()`.

## 2. Standar Multi Currency
- Gunakan sistem existing project, jangan membuat sistem currency baru.
- Currency display mengikuti session (`currency`) dengan fallback default aktif.
- Formatting amount wajib melalui formatter existing:
  - `<x-money ... />` atau wrapper standar `<x-ui.money ... />`.
  - `\App\Support\Currency::format(...)` untuk kasus non-Blade bila diperlukan.
- Konversi nominal input display ke IDR untuk penyimpanan tetap melalui pattern existing (`NormalizesDisplayCurrencyToIdr` di controller concern).

## 3. Standar Light/Dark Mode (Wajib)
- Setiap halaman yang diubah wajib tetap terbaca dan usable pada mode light dan dark.
- Gunakan utility class berpasangan (`text-*` + `dark:text-*`, `bg-*` + `dark:bg-*`, `border-*` + `dark:border-*`) untuk elemen user-facing.
- Hindari hardcoded warna inline yang tidak punya pasangan mode dark.
- Hover/focus/active state wajib aman di kedua mode (kontras cukup, teks tetap terbaca).
- Komponen baru harus mengikuti pola theme existing project, jangan membuat theme switch logic baru.

## 4. Helper Translation yang Harus Digunakan
- `ui_phrase('...')`
- `ui_choice('...', $count, [...])`
- `ui_token('...')`
- `ui_entity('...')`
- `ui_action('...')`

## 5. Helper/Formatter Currency yang Harus Digunakan
- Blade:
  - `<x-money :amount="$value" :currency="$fromCurrency" />`
  - `<x-ui.money :amount="$value" :currency="$fromCurrency" />`
- PHP:
  - `\App\Support\Currency::format($amount, $fromCurrency, $toCurrency)`
  - `\App\Support\Currency::convert($amount, $fromCurrency, $toCurrency)`

## 6. Contoh Benar dan Salah
### Translation
- Benar:
  - `{{ ui_phrase('Add Booking') }}`
  - `{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Bookings')]) }}`
- Salah:
  - `{{ 'Add Booking' }}`
  - `{{ ucfirst($status) }}`

### Currency
- Benar:
  - `<x-ui.money :amount="$invoice->total_amount" :currency="'IDR'" />`
  - `<x-money :amount="$payment->amount" :currency="$payment->currency_code" />`
- Salah:
  - `{{ number_format($invoice->total_amount, 0, ',', '.') }}`
  - `Rp {{ $amount }}`

## 7. Daftar Module Wajib Dicek
- Dashboard
- Customers / Agents
- Inquiries
- Itineraries
- Quotations
- Quotation Validation
- Bookings
- Booking Reconciliation
- Vouchers
- Invoices
- Payments
- Vendors / Providers
- Activities
- Food & Beverage
- Tourist Attractions
- Transports
- Hotels / Accommodations
- Island Transfers
- Reports
- Users / Roles / Permissions
- Service Manager

## 8. Checklist QA Multi Language
- Semua `page_title`, `page_subtitle`, breadcrumb translated.
- Semua button/action translated.
- Semua table header translated.
- Semua form label, placeholder, helper text translated.
- Semua alert/flash/modal text translated.
- Semua empty state text translated.
- Semua status label translated.
- Tidak ada hardcoded English/Indonesia pada halaman yang disentuh.

## 9. Checklist QA Multi Currency
- Semua amount/price/rate/total/balance/paid/discount/markup/cancellation fee memakai komponen/formatter currency existing.
- Tidak ada `number_format` manual untuk nominal uang di Blade halaman yang disentuh.
- Currency symbol/format konsisten antar table, card, summary, dan detail.
- Nilai null/empty nominal fallback aman (`-` atau 0 via formatter).
- Tidak ada query database baru di komponen UI money wrapper.

## 10. Checklist QA Light/Dark Mode
- Semua card, table, form, dropdown, modal, badge, dan empty state tetap terbaca di light/dark mode.
- Tidak ada foreground/background dengan kontras rendah pada mode dark.
- Tidak ada elemen interaktif (button/link/input) yang hilang state hover/focus pada mode dark.
- Tidak ada hardcoded inline color tanpa fallback dark mode.
- Halaman yang diubah dites minimal sekali pada mode light dan dark sebelum dianggap selesai.

## 11. Aturan Eksekusi Step UI Berikutnya
- Setiap file Blade yang diedit wajib lolos audit i18n + currency + light/dark mode sebelum commit.
- Setiap step wajib update:
  - `docs/blueprint/VOYEX_UI_STANDARDIZATION_CHECKLIST.md`
  - `docs/technical/VOYEX_UI_REFACTOR_CHANGELOG.md`
