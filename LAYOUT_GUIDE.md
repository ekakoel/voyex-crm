# Layout Guide

This guide documents layout conventions for consistent headers and page actions.

## Page Header + Breadcrumbs (Default)
The master layout automatically renders the page header, description, and breadcrumbs.
Use sections below to override the text or add actions.

```blade
@extends('layouts.master')

@section('page_title', 'Customers')
@section('page_subtitle', 'Manage customer data')
@section('page_actions')
    <a href="{{ route('customers.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
        Add Customer
    </a>
@endsection

@section('content')
    <div class="space-y-6">
        ...
    </div>
@endsection
```

Breadcrumbs are generated automatically from the route name:
`Dashboard > Resource > Action`. You can override the title/subtitle to keep the UI consistent.

## Page Actions Header (Recommended)
Use `@section('page_actions')` to render right-aligned actions in the master layout.

```blade
@extends('layouts.master')

@section('page_actions')
    <a href="{{ route('inquiries.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
        Add Inquiry
    </a>
@endsection

@section('content')
    <div class="space-y-6">
        ...
    </div>
@endsection
```

### Starter Template
Use the module page starter template as a baseline for new pages:

`resources/views/templates/module-page.blade.php`

## Optional Header Timestamp
Add timestamps inside `page_actions` if needed.

```blade
@section('page_actions')
    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
        {{ now()->format('l, j F Y') }}
    </span>
@endsection
```

## Hiding the Page Header
If a page should not render a header at all:

```blade
@section('page_header_hidden', '1')
```

## Standard Grid Baseline (Global)
To keep UI/UX consistent across modules, use this split as default for all non-dashboard pages:

- `Index`: `4 / 8` (left filter/sidebar, right data/list)
- `Create/Edit/Detail`: `8 / 4` (left form/content, right supporting panel, active on `xl` breakpoint to match Itinerary baseline)
- `Dashboard`: **excluded** (can use custom KPI/analytics layout)

CSS utility classes are available in `resources/css/app.css`:

- `.module-grid-3-9`
- `.module-grid-8-4`
- `.module-grid-main`
- `.module-grid-side`

### Index Template (4/8)
```blade
@section('content')
    <div class="space-y-6 module-page">
        <div class="module-grid-3-9">
            <aside class="module-grid-side module-card p-4">
                {{-- filters / quick actions --}}
            </aside>

            <section class="module-grid-main module-card p-6">
                {{-- table / cards / list --}}
            </section>
        </div>
    </div>
@endsection
```

### Create/Edit/Detail Template (8/4)
```blade
@section('content')
    <div class="space-y-6 module-page">
        <div class="module-grid-8-4">
            <section class="module-grid-main module-form-wrap">
                {{-- form or main detail --}}
            </section>

            <aside class="module-grid-side space-y-6">
                {{-- audit info / map / metadata --}}
            </aside>
        </div>
    </div>
@endsection
```

### Breakpoint Behavior
- `module-grid-8-4` and `module-grid-8-4` stack in mobile/tablet and split to `8/4` on `xl` (Itinerary baseline).
- `module-grid-3-9` and `module-grid-4-8` remain for index/list pages (`md` and up).

### Migration Rule
- Use this baseline on all module pages going forward.
- For existing pages, migrate per module to avoid large risky refactors in one release.
- Keep dashboard pages on their own layout system.

## Map Standard Section (Create/Edit)
For modules that use Google Maps URL autofill, use a single reusable partial:

`resources/views/components/map-standard-section.blade.php`

Required field order inside the section:
1. `Location on Map (open map)`
2. `Map URL (Google Maps)`
3. `Latitude` + `Longitude`
4. `Address`
5. `City` + `Province`
6. `Country`
7. `Destination`

Implementation note:
- Keep non-standard location fields (for example `location` hidden input, `timezone`) outside this partial.


