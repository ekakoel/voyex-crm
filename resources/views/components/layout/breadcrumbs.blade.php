@php
    $routeName = request()->route()?->getName();
    $actionLabels = [
        'index' => 'List',
        'create' => 'Create',
        'edit' => 'Edit',
        'show' => 'Detail',
    ];
    $scopeLabels = [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'sales' => 'Sales',
        'operations' => 'Operations',
        'finance' => 'Finance',
        'director' => 'Director',
    ];
    $labelOverrides = array_merge($scopeLabels, [
        'quotation-templates' => 'Quotation Templates',
        'tourist-attractions' => 'Tourist Attractions',
        'services' => 'Modules',
        'profile' => 'Profile',
    ]);

    $items = [];
    if (Route::has('dashboard')) {
        $items[] = ['label' => 'Dashboard', 'url' => route('dashboard')];
    }

    if ($routeName) {
        $isDashboardRoute = $routeName === 'dashboard'
            || $routeName === 'superadmin.dashboard'
            || str_starts_with($routeName, 'dashboard.');
        if ($isDashboardRoute) {
            $items = [['label' => 'Dashboard', 'url' => null]];
        }

        $parts = explode('.', $routeName);

        $resolveLabel = function (string $segment) use ($labelOverrides): string {
            return $labelOverrides[$segment]
                ?? \Illuminate\Support\Str::of($segment)->replace(['-', '_'], ' ')->title()->toString();
        };
        $resolveRoute = function (string $candidate) {
            if (Route::has($candidate)) {
                return route($candidate);
            }
            if (Route::has($candidate . '.index')) {
                return route($candidate . '.index');
            }

            return null;
        };
        $resolveEntityLabel = function () {
            $route = request()->route();
            if (! $route) {
                return null;
            }

            $parameters = array_reverse($route->parametersWithoutNulls());
            foreach ($parameters as $value) {
                if (is_object($value) && method_exists($value, 'getAttribute')) {
                    foreach (['inquiry_number', 'quotation_number', 'booking_number', 'code', 'title', 'name'] as $field) {
                        $attr = $value->getAttribute($field);
                        if (is_string($attr) && trim($attr) !== '') {
                            return trim($attr);
                        }
                    }

                    $id = method_exists($value, 'getKey') ? $value->getKey() : null;
                    return \Illuminate\Support\Str::of(class_basename($value))->replace(['-', '_'], ' ')->title()->toString()
                        . ($id ? ' #' . $id : '');
                }

                if (is_scalar($value) && (string) $value !== '') {
                    return (string) $value;
                }
            }

            return null;
        };

        // Dashboard family normalization:
        // superadmin.dashboard => Dashboard > Super Admin > Dashboard
        // dashboard.admin     => Dashboard > Admin > Dashboard
        if (! $isDashboardRoute) {
            if (($parts[0] ?? null) === 'superadmin' && ($parts[1] ?? null) === 'dashboard') {
                $items[] = ['label' => 'Super Admin', 'url' => Route::has('superadmin.dashboard') ? route('superadmin.dashboard') : null];
                $items[] = ['label' => 'Dashboard', 'url' => null];
            } elseif (($parts[0] ?? null) === 'dashboard' && isset($parts[1]) && isset($scopeLabels[$parts[1]])) {
                $scope = $parts[1];
                $scopeDashboardRoute = 'dashboard.' . $scope;
                $items[] = ['label' => $scopeLabels[$scope], 'url' => Route::has($scopeDashboardRoute) ? route($scopeDashboardRoute) : null];
                $items[] = ['label' => 'Dashboard', 'url' => null];
            } else {
                $action = null;
                $lastPart = end($parts);
                if (array_key_exists($lastPart, $actionLabels)) {
                    $action = $lastPart;
                    array_pop($parts);
                }

                $consumedPrefix = '';

                if (!empty($parts) && isset($scopeLabels[$parts[0]])) {
                    $scope = array_shift($parts);
                    $scopeDashboardRoute = $scope . '.dashboard';
                    $items[] = ['label' => $scopeLabels[$scope], 'url' => Route::has($scopeDashboardRoute) ? route($scopeDashboardRoute) : null];
                    $consumedPrefix = $scope;
                }

                foreach ($parts as $index => $part) {
                    $segment = (string) $part;
                    $label = $resolveLabel($segment);
                    $chain = implode('.', array_slice($parts, 0, $index + 1));
                    $candidate = $consumedPrefix !== '' ? ($consumedPrefix . '.' . $chain) : $chain;
                    $url = $resolveRoute($candidate);

                    $items[] = ['label' => $label, 'url' => $url];
                }

                if ($action && $action !== 'index') {
                    $items[] = ['label' => $actionLabels[$action], 'url' => null];
                }

                if (in_array($action, ['show', 'edit'], true)) {
                    $entityLabel = $resolveEntityLabel();
                    if ($entityLabel) {
                        $items[] = ['label' => $entityLabel, 'url' => null];
                    }
                }
            }
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
