# Internationalization (i18n) Guide

This project now uses centralized Laravel localization with English as the default source language.

## Active Locales

- `en` (English)
- `zh_Hant` (Chinese Traditional)
- `zh_Hans` (Chinese Simplified)

Configured in: `config/app.php` under `supported_locales`.

## Runtime Locale Flow

1. User submits locale to `POST /locale` (`locale.set` route).
2. Locale value is stored in session key: `locale`.
3. `App\Http\Middleware\SetLocale` applies locale for every web request.
4. Missing keys automatically fall back to English (`fallback_locale = en`).

## Top Navbar Language Switch

- Main app top navbar (`resources/views/layouts/master.blade.php`) provides a language switch dropdown.
- The switch posts to `locale.set` and keeps user on the same page (`back()` redirect).
- Current locale is shown as compact label:
  - `EN`
  - `繁` (Traditional Chinese / `zh_Hant`)
  - `简` (Simplified Chinese / `zh_Hans`)
- Locale labels come from `config/app.php` (`supported_locales`).

## Translation Files

- Main UI dictionary: `lang/<locale>/ui_core.php`
- Itinerary form dictionary: `lang/<locale>/itinerary_form.php`
- Core Laravel messages:
  - `lang/<locale>/auth.php`
  - `lang/<locale>/passwords.php`
  - `lang/<locale>/pagination.php`
  - `lang/<locale>/validation.php`
- JSON strings (optional): `lang/<locale>.json`

## Standardization Rules

- Use English source copy in `lang/en/*`.
- Do not hardcode user-facing strings in Blade or Controller.
- For inline JavaScript inside Blade, keep strings translatable by injecting via `@json(__('...'))`.
- For shared UI text, use `ui_phrase('Exact English Phrase')` and ensure the same exact phrase exists in `ui_core.php`.
- Avoid technical/prefix-only keys for layout labels (example: do not use `sidebar_dashboard`).
- Preferred pattern for global navigation text:
  - Blade/PHP: `ui_phrase('Dashboard')`
  - `lang/en/ui_core.php`: `'Dashboard' => 'Dashboard'`
  - `lang/zh_Hant/ui_core.php`: `'Dashboard' => '儀表板'`
  - `lang/zh_Hans/ui_core.php`: `'Dashboard' => '仪表板'`

## Migration Strategy for Remaining Pages

1. Replace hardcoded text in each module with translatable phrase calls.
2. Add phrase entries to `lang/en/ui_core.php` (or module-specific file if needed).
3. Sync the same phrase entries to `zh_Hant` and `zh_Hans` files.
4. Keep wording concise and consistent with existing tone.

## PDF Chinese Support (Traditional / Simplified)

PDF rendering uses DOMPDF and requires a CJK-compatible font to display Chinese characters correctly.

### Supported PDF Locales

- `en`
- `zh_Hant`
- `zh_Hans`

### How to Activate Chinese PDF

1. Place one of these font files into `resources/fonts/cjk/` (recommended):
   - Traditional: `NotoSansTC-Regular.ttf` or `NotoSerifTC-Regular.ttf`
   - Simplified: `NotoSansSC-Regular.ttf` or `NotoSerifSC-Regular.ttf`
2. Open PDF route with locale query:
   - Itinerary PDF: `...?locale=zh_Hant` or `...?locale=zh_Hans`
   - Quotation PDF: `...?locale=zh_Hant` or `...?locale=zh_Hans`
3. If CJK font file is missing, PDF falls back to `DejaVu Sans`.

### Implementation Notes

- Locale-aware font loading is handled in:
  - `App\Http\Controllers\Admin\ItineraryController`
  - `App\Http\Controllers\Sales\QuotationController`
- PDF templates consume injected variables:
  - `$pdfFontFaceCss`
  - `$pdfFontFamilyCss`
