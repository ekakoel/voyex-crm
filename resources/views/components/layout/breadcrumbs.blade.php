@php
    $routeName = request()->route()?->getName();
    $actionLabels = [
        'index' => 'List',
        'create' => 'Create',
        'edit' => 'Edit',
        'show' => 'Detail',
    ];
    $labelOverrides = [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'sales' => 'Sales',
        'operations' => 'Operations',
        'finance' => 'Finance',
        'director' => 'Director',
    ];

    $items = [];
    if (Route::has('dashboard')) {
        $items[] = ['label' => 'Dashboard', 'url' => route('dashboard')];
    }

    if ($routeName && $routeName !== 'dashboard') {
        $parts = explode('.', $routeName);
        $action = null;
        $lastPart = end($parts);
        if (array_key_exists($lastPart, $actionLabels)) {
            $action = $lastPart;
            array_pop($parts);
        }

        foreach ($parts as $index => $part) {
            $segment = (string) $part;
            $label = $labelOverrides[$segment] ?? \Illuminate\Support\Str::of($segment)->replace(['-', '_'], ' ')->title()->toString();
            $url = null;

            if ($index === 0) {
                $scopeDashboardRoute = $segment . '.dashboard';
                if (Route::has($scopeDashboardRoute)) {
                    $url = route($scopeDashboardRoute);
                }
            } else {
                $candidate = implode('.', array_slice($parts, 0, $index + 1)) . '.index';
                if (Route::has($candidate)) {
                    $url = route($candidate);
                }
            }

            $items[] = ['label' => $label, 'url' => $url];
        }

        if ($action && $action !== 'index') {
            $items[] = ['label' => $actionLabels[$action], 'url' => null];
        }
    }
@endphp

@if (count($items) > 1)
    <nav class="mb-4 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2 text-sm">
            @foreach ($items as $index => $item)
                @php
                    $isLast = $index === count($items) - 1;
                @endphp
                <li class="flex items-center gap-2">
                    @if (! $isLast && ! empty($item['url']))
                        <a href="{{ $item['url'] }}"
                           class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-600 transition-colors duration-150 hover:bg-indigo-100 hover:text-indigo-700 dark:bg-gray-700/70 dark:text-gray-300 dark:hover:bg-indigo-900/40 dark:hover:text-indigo-300">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="{{ $isLast ? 'inline-flex items-center rounded-md bg-indigo-600 px-2 py-1 font-semibold text-white dark:bg-indigo-500' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $item['label'] }}
                        </span>
                    @endif
                    @if (! $isLast)
                        <span class="text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
