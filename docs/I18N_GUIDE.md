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

## Translation Files

- Main UI dictionary: `lang/<locale>/ui.php`
- Itinerary form dictionary: `lang/<locale>/itinerary_form.php`
- JSON strings (optional): `lang/<locale>.json`

## Standardization Rules

- Use English source copy in `lang/en/*`.
- Do not hardcode user-facing strings in Blade or Controller.
- Use translation keys:
  - Blade: `{{ __('ui.common.save') }}`
  - PHP: `__('ui.modules.bookings.final_locked_edit')`

## Migration Strategy for Remaining Pages

1. Replace hardcoded text in each module with translation keys.
2. Add keys to `lang/en/ui.php` (or module-specific file if needed).
3. Sync keys to `zh_Hant` and `zh_Hans` files.
4. Keep wording concise and consistent with existing tone.

