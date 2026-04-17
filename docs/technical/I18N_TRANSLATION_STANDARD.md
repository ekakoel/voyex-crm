# VOYEX CRM I18N Translation Standard

Last Updated: 2026-04-17

## Goal
- Ensure all UI text is translation-ready.
- Avoid hardcoded literals in Blade views.
- Keep future multi-language rollout low risk.

## Scope (Mandatory)
- All files under:
  - `resources/views/modules/**/create.blade.php`
  - `resources/views/modules/**/edit.blade.php`
  - `resources/views/modules/**/show.blade.php`
- Module partials used by the pages above (labels/help text/button text/modal text).

## Required Rules
1. Never hardcode user-facing text in Blade.
2. Use translation keys from `lang/en/ui.php`.
3. Use `__('ui...')` for single labels and messages.
4. Use placeholders for dynamic text, example: `__('ui.common.waiting_for', ['names' => $names])`.
5. Keep keys grouped by domain:
   - shared labels: `ui.common.*`
   - module labels: `ui.modules.{module_key}.*`
6. New module/page cannot be considered done without translation keys.

## Recommended Key Pattern
- Page-level:
  - `ui.modules.{module}.page_title`
  - `ui.modules.{module}.page_subtitle`
  - `ui.modules.{module}.create_page_title`
  - `ui.modules.{module}.edit_page_title`
  - `ui.modules.{module}.show_page_title`
- Form actions:
  - `ui.modules.{module}.save_*`
  - `ui.modules.{module}.update_*`
- Generic actions:
  - `ui.common.back`, `ui.common.edit`, `ui.common.view_detail`, `ui.common.save`, `ui.common.cancel`

## Example
```blade
@section('page_title', __('ui.modules.users.create_page_title'))
@section('page_subtitle', __('ui.modules.users.create_page_subtitle'))

<a href="{{ route('users.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
```

## QA Checklist (Before Merge)
1. Run syntax check:
   - `php -l lang/en/ui.php`
2. Ensure no missing UI keys referenced in create/edit/show pages.
3. Build Blade cache:
   - `php artisan view:cache`
4. Update roadmap changelog entry for i18n-related changes.


