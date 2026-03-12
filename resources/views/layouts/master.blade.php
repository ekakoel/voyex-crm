<!DOCTYPE html>
<html lang="en" x-data="siteData()" :class="dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appTitle = trim((string) ($companySettings->company_name ?? 'VOYEX CRM'));
        $faviconPath = $companySettings->favicon_path ?? null;
        $faviconVersion = !empty($companySettings?->updated_at) ? $companySettings->updated_at->timestamp : null;
        $faviconUrl = $faviconPath ? asset('storage/' . $faviconPath) . ($faviconVersion ? ('?v=' . $faviconVersion) : '') : null;
        $faviconExt = $faviconPath ? strtolower((string) pathinfo($faviconPath, PATHINFO_EXTENSION)) : null;
        $faviconMime = match ($faviconExt) {
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/x-icon',
        };
    @endphp
    <title>{{ $appTitle !== '' ? $appTitle : 'VOYEX CRM' }}</title>
    @if ($faviconUrl)
        <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @endif
    @vite(['resources/css/app.css','resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300" data-currency="{{ $currentCurrency ?? 'IDR' }}">

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="fixed inset-y-0 left-0 z-40 bg-primary text-white transform transition-all duration-300
                  w-64 md:static md:translate-x-0 md:flex-shrink-0 overflow-y-auto max-h-screen" :class="{
                      'translate-x-0': sidebarOpen,
                      '-translate-x-full md:translate-x-0': !sidebarOpen,
                      'md:w-20 sidebar-is-collapsed': sidebarCollapsed,
                      'md:w-64': !sidebarCollapsed
                  }">

        <div class="p-4 border-b border-gray-700 flex items-center justify-between gap-2">
            <div class="text-xl font-bold whitespace-nowrap overflow-hidden"
                 :class="sidebarCollapsed ? 'md:hidden' : 'block'">
                {{ $appTitle !== '' ? $appTitle : 'VOYEX CRM' }}
            </div>

            <button type="button"
                    class="hidden md:inline-flex items-center justify-center h-9 w-9 rounded-lg hover:bg-gray-700 transition"
                    @click="toggleSidebar()"
                    :title="sidebarCollapsed ? 'Show icons + labels' : 'Show icons only'">
                <svg x-show="sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.293 15.707a1 1 0 010-1.414L15.586 11H4a1 1 0 110-2h11.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.707 4.293a1 1 0 010 1.414L4.414 9H16a1 1 0 010 2H4.414l3.293 3.293a1 1 0 01-1.414 1.414l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <nav class="mt-6 space-y-2 px-4">
            @isset($menuItems)
                @foreach ($menuItems as $item)
                    @if (($item['type'] ?? null) === 'separator')
                        <div class="my-2 border-t border-white/25"></div>
                        @continue
                    @endif
                    @if (($item['type'] ?? null) === 'label')
                        <div class="px-4 pt-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-white/55 sidebar-label">{{ $item['title'] }}</div>
                        @continue
                    @endif

                    @php
                        $hasChildren = ! empty($item['children']) && is_array($item['children']);
                        $isDashboardShortcut = ($item['route'] ?? '') === 'dashboard' && request()->routeIs('dashboard.*');
                        $isItemActive = $isDashboardShortcut || request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
                        $isChildActive = false;
                        if ($hasChildren) {
                            foreach ($item['children'] as $child) {
                                if (Route::has($child['route']) && (request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*'))) {
                                    $isChildActive = true;
                                    break;
                                }
                            }
                        }

                        $icon = strtolower($item['icon'] ?? 'grid');
                    @endphp

                    @if ($hasChildren)
                        <div x-data="{ openChildren: {{ $isChildActive ? 'true' : 'false' }} }" 
                             @keydown.escape="openChildren = false">
                            <button type="button"
                                    class="sidebar-nav-item w-full group flex items-center gap-3 px-4 py-2 rounded-lg transition-colors duration-200 text-left text-white/90 hover:text-white
                                           {{ $isChildActive ? 'bg-gray-700 font-semibold is-active' : 'hover:bg-gray-700' }}"
                                    :class="sidebarCollapsed ? 'md:justify-center md:px-2' : ''"
                                    :title="sidebarCollapsed ? '{{ $item['title'] }}' : ''"
                                    @click="openChildren = !openChildren">
                                <span class="inline-flex h-5 w-5 items-center justify-center">
                                    <i class="fa-solid fa-{{ $icon }}"></i>
                                </span>
                                <span class="flex-1 truncate sidebar-label">{{ $item['title'] }}</span>
                                <span class="text-xs transition-transform duration-200 sidebar-arrow" :class="openChildren ? 'rotate-90' : ''">
                                    <i class="fa-solid fa-caret-right"></i>
                                </span>
                            </button>

                            <template x-if="!sidebarCollapsed">
                                <div x-show="openChildren" x-transition class="mt-1 ml-6 space-y-1">
                                    @foreach ($item['children'] as $child)
                                        @if (Route::has($child['route']))
                                            <a href="{{ route($child['route']) }}"
                                               class="sidebar-sub-item flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-white/85 hover:text-white transition-colors duration-200
                                                      {{ request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*') ? 'bg-gray-700/80 font-semibold is-active' : 'hover:bg-gray-700/70' }}">
                                                <span class="inline-flex h-4 w-4 items-center justify-center text-xs">
                                                    <i class="fa-solid fa-{{ $child['icon'] ?? 'list' }}"></i>
                                                </span>
                                                <span>{{ $child['title'] }}</span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </template>
                        </div>
                    @else
                        @if (Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                               class="sidebar-nav-item group flex items-center gap-3 px-4 py-2 rounded-lg transition-colors duration-200 text-white/90 hover:text-white
                                      {{ $isItemActive ? 'bg-gray-700 font-semibold is-active' : 'hover:bg-gray-700' }}"
                               :class="sidebarCollapsed ? 'md:justify-center md:px-2' : ''"
                               :title="sidebarCollapsed ? '{{ $item['title'] }}' : ''">
                                <span class="inline-flex h-5 w-5 items-center justify-center">
                                    <i class="fa-solid fa-{{ $icon }}"></i>
                                </span>
                                <span class="truncate sidebar-label">{{ $item['title'] }}</span>
                            </a>
                        @endif
                    @endif
                @endforeach
            @endisset
        </nav>
    </aside>

    <!-- Overlay mobile -->
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/50 md:hidden"
        x-transition.opacity>
    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col">

        <header class="sticky top-0 z-20 bg-white/95 dark:bg-gray-800/95 backdrop-blur shadow-sm px-3 py-3 sm:px-4 sm:py-3 md:px-6 md:py-4 flex items-center justify-between gap-3">
            <!-- mobile-only button -->
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="hidden md:block"></div>

            <div class="ml-auto flex items-center gap-3 sm:gap-4 md:gap-6 min-w-0">

                <!-- Currency Switch -->
                <div class="hidden sm:flex items-center gap-2">
                    <form method="POST" action="{{ route('currency.set') }}">
                        @csrf
                        <div class="relative">
                            <select
                                name="currency"
                                onchange="this.form.submit()"
                                class="h-9 rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-semibold uppercase tracking-wide text-gray-700 shadow-sm transition hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-indigo-600"
                            >
                                @forelse (($currencyOptions ?? collect()) as $currencyOption)
                                    <option value="{{ $currencyOption->code }}" @selected(($currentCurrency ?? 'IDR') === $currencyOption->code)>{{ $currencyOption->code }}</option>
                                @empty
                                    <option value="IDR" selected>IDR</option>
                                @endforelse
                            </select>
                        </div>
                    </form>
                    @can('module.currencies.access')
                        <a href="{{ route('currencies.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-coins"></i>
                        </a>
                    @endcan
                </div>

                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-200 transition-colors duration-200 dark:border-gray-700"
                        :class="dark ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-500 hover:text-amber-500'"
                        :title="dark ? 'Dark mode on' : 'Light mode on'">
                    <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <svg x-show="dark" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div x-data="{ open: false }" class="relative shrink-0">
                    <button @click="open = !open"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-2.5 py-1.5 text-gray-700 dark:border-gray-700 dark:text-gray-200">
                        <span class="inline-flex h-4 w-4 items-center justify-center text-xs">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <span class="hidden sm:inline truncate max-w-[140px] md:max-w-[180px]">{{ auth()->user()->name }}</span>
                        <span class="sm:hidden text-sm font-medium">User</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>

                    <div x-show="open"
                         @click.outside="open = false"
                         x-cloak class="absolute right-0 mt-2 w-44 sm:w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border dark:border-gray-700 py-1 z-10">

                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Profile
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Logout
                            </button>
                        </form>

                    </div>
                </div>

            </div>

        </header>

        <!-- PAGE CONTENT -->
        <main class="app-content flex-1 p-4 sm:p-6 overflow-y-auto">
            @php
                $routeName = (string) (Route::currentRouteName() ?? '');
                $routeParts = $routeName !== '' ? explode('.', $routeName) : [];
                $routeAction = !empty($routeParts) ? strtolower((string) end($routeParts)) : 'index';
                $routeResource = !empty($routeParts) ? strtolower((string) $routeParts[0]) : 'dashboard';
                $defaultTitle = \Illuminate\Support\Str::headline(str_replace('-', ' ', $routeResource));
                $defaultSubtitle = match ($routeAction) {
                    'index' => 'Browse and manage data',
                    'create', 'store' => 'Create a new record',
                    'edit', 'update' => 'Update existing data',
                    'show' => 'Review complete detail information.',
                    default => 'Page information and actions',
                };
                $pageTitle = trim((string) $__env->yieldContent('page_title')) !== ''
                    ? trim((string) $__env->yieldContent('page_title'))
                    : $defaultTitle;
                $pageSubtitle = trim((string) $__env->yieldContent('page_subtitle')) !== ''
                    ? trim((string) $__env->yieldContent('page_subtitle'))
                    : $defaultSubtitle;
                $hidePageHeader = trim((string) $__env->yieldContent('page_header_hidden')) === '1';
                $actionLabel = match ($routeAction) {
                    'index' => 'List',
                    'create', 'store' => 'Create',
                    'edit', 'update' => 'Edit',
                    'show' => 'Detail',
                    default => \Illuminate\Support\Str::headline(str_replace('-', ' ', $routeAction)),
                };
                $resourceLabel = \Illuminate\Support\Str::headline(str_replace('-', ' ', $routeResource));
                $breadcrumbs = [
                    [
                        'label' => 'Dashboard',
                        'url' => \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/'),
                    ],
                ];
                if ($routeResource !== 'dashboard') {
                    $indexRoute = $routeResource . '.index';
                    $breadcrumbs[] = [
                        'label' => $resourceLabel,
                        'url' => \Illuminate\Support\Facades\Route::has($indexRoute) ? route($indexRoute) : null,
                    ];
                    $breadcrumbs[] = [
                        'label' => $actionLabel,
                    ];
                }
            @endphp
            @unless ($hidePageHeader)
                <section class="mb-4 sm:mb-5">
                    <x-page-header :title="$pageTitle" :description="$pageSubtitle" :breadcrumbs="$breadcrumbs">
                        @hasSection('page_actions')
                            @yield('page_actions')
                        @endif
                    </x-page-header>
                </section>
            @endunless
            @yield('content')
        </main>

    </div>
</div>

<script>
    function siteData() {
        return {
            dark: @json((auth()->user()->theme_preference ?? 'light') === 'dark'),
            sidebarOpen: false,
            sidebarCollapsed: false,
            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
            },
            async toggleTheme() {
                this.dark = !this.dark;
                try {
                    await fetch(@json(route('profile.theme')), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            theme: this.dark ? 'dark' : 'light'
                        })
                    });
                } catch (e) {
                    // Keep UI responsive even if request fails.
                }
            }
        };
    }

    @php
        $currencyMeta = \App\Support\Currency::meta($currentCurrency ?? 'IDR');
        $currencyRateToIdr = is_array($currencyMeta) ? (float) ($currencyMeta['rate_to_idr'] ?? 1) : 1;
        $currencyDecimals = is_array($currencyMeta) ? (int) ($currencyMeta['decimal_places'] ?? 0) : 0;
    @endphp
    window.appCurrency = @json($currentCurrency ?? 'IDR');
    window.appCurrencyRateToIdr = @json($currencyRateToIdr);
    window.appCurrencyDecimals = @json($currencyDecimals);

    function attachRequiredMarkers(root = document) {
        const fields = root.querySelectorAll('input[required], select[required], textarea[required]');

        fields.forEach((field) => {
            if (field.type === 'hidden' || field.disabled) {
                return;
            }

            let label = null;
            if (field.id) {
                label = document.querySelector(`label[for="${CSS.escape(field.id)}"]`);
            }

            if (!label) {
                label = field.closest('label');
            }

            if (!label) {
                return;
            }

            if (label.querySelector('.required-asterisk')) {
                return;
            }

            const marker = document.createElement('span');
            marker.className = 'required-asterisk';
            marker.setAttribute('aria-hidden', 'true');
            marker.textContent = '*';
            label.appendChild(marker);
        });
    }

    function attachMoneyHints(root = document) {
        const currency = window.appCurrency || 'IDR';
        const moneyPattern = /(price|rate|amount|fee|cost|discount|total)/i;
        const fields = root.querySelectorAll('input[type="number"], input[inputmode="decimal"], input[inputmode="numeric"]');

        fields.forEach((field) => {
            if (field.dataset.moneyHintBound === '1' || field.dataset.moneyInput === '1') {
                return;
            }

            const name = (field.getAttribute('name') || field.id || '').trim();
            if (!name || !moneyPattern.test(name)) {
                return;
            }

            const hint = document.createElement('div');
            hint.className = 'money-hint text-[10px] text-gray-500 dark:text-gray-400 mt-1';
            hint.textContent = `Currency: ${currency}`;

            field.insertAdjacentElement('afterend', hint);
            field.dataset.moneyHintBound = '1';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        attachRequiredMarkers(document);
        attachMoneyHints(document);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        attachRequiredMarkers(node);
                        attachMoneyHints(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });

    function initLocationAutofill(root = document) {
        const blocks = root.querySelectorAll('[data-location-autofill]');
        blocks.forEach((block) => {
            if (block.dataset.locationBound === '1') {
                return;
            }
            block.dataset.locationBound = '1';

            const endpoint = block.getAttribute('data-location-resolve-url') || @json(route('location.resolve-google-map'));
            const urlInput = block.querySelector('[data-location-field="google_maps_url"]');
            const destinationInput = block.querySelector('[data-location-field="destination_id"]');
            const statusNode = block.querySelector('[data-location-status]');
            const triggerButton = block.querySelector('[data-location-autofill-trigger]');

            if (!urlInput || !endpoint) {
                return;
            }

            const setStatus = (message, isError = false) => {
                if (!statusNode) return;
                statusNode.textContent = message;
                statusNode.classList.remove('hidden', 'text-emerald-600', 'text-rose-600', 'dark:text-emerald-400', 'dark:text-rose-400');
                statusNode.classList.add(isError ? 'text-rose-600' : 'text-emerald-600');
                statusNode.classList.add(isError ? 'dark:text-rose-400' : 'dark:text-emerald-400');
            };

            const clearStatus = () => {
                if (!statusNode) return;
                statusNode.textContent = '';
                statusNode.classList.add('hidden');
            };

            const applyField = (field, value) => {
                const input = block.querySelector(`[data-location-field="${field}"]`);
                if (!input) {
                    return;
                }

                if (value === null || value === undefined) {
                    return;
                }

                if (input.tagName === 'SELECT') {
                    const normalized = String(value);
                    if ([...input.options].some((option) => option.value === normalized)) {
                        input.value = normalized;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        return;
                    }

                    if (field === 'destination_id') {
                        return;
                    }
                }

                input.value = String(value);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const pickDestinationByProvince = (province) => {
                if (!destinationInput || !province) {
                    return;
                }

                const normalized = String(province).trim().toLowerCase();
                for (const option of destinationInput.options) {
                    const optionProvince = String(option.dataset.province || '').trim().toLowerCase();
                    if (optionProvince !== '' && optionProvince === normalized) {
                        destinationInput.value = option.value;
                        destinationInput.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            };

            const resolveLocation = async () => {
                const googleMapsUrl = (urlInput.value || '').trim();
                if (!googleMapsUrl) {
                    clearStatus();
                    return;
                }

                setStatus('Resolving location from Google Maps link...');

                try {
                    const query = new URLSearchParams({ google_maps_url: googleMapsUrl });
                    const response = await fetch(`${endpoint}?${query.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('resolve_failed');
                    }

                    const payload = await response.json();
                    const data = payload?.data || {};

                    applyField('latitude', data.latitude);
                    applyField('longitude', data.longitude);
                    applyField('country', data.country);
                    applyField('city', data.city);
                    applyField('province', data.province);
                    applyField('address', data.address);
                    applyField('location', data.location);
                    applyField('timezone', data.timezone);

                    if (data.destination_id) {
                        applyField('destination_id', data.destination_id);
                    } else if (data.province) {
                        pickDestinationByProvince(data.province);
                    }

                    setStatus('Location fields have been auto-filled.');
                } catch (_) {
                    setStatus('Unable to resolve this link. Please fill location fields manually.', true);
                }
            };

            urlInput.addEventListener('change', resolveLocation);
            urlInput.addEventListener('blur', resolveLocation);
            if (triggerButton) {
                triggerButton.addEventListener('click', resolveLocation);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initLocationAutofill(document);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        initLocationAutofill(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>
@stack('scripts')

</body>
</html>
