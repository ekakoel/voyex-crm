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
