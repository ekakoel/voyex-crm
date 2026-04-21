<!DOCTYPE html>
<html lang="en" x-data="siteData()" :class="dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appTitle = trim((string) ($companySettings->company_name ?? 'VOYEX CRM'));
        $logoPath = $companySettings->logo_path ?? null;
        $logoVersion = !empty($companySettings?->updated_at) ? $companySettings->updated_at->timestamp : null;
        $logoUrl = $logoPath
            ? (\App\Support\ImageThumbnailGenerator::resolvePublicUrl($logoPath)
                ?? \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($logoPath))
            : null;
        if ($logoUrl && $logoVersion) {
            $logoUrl .= '?v=' . $logoVersion;
        }
        $faviconPath = $companySettings->favicon_path ?? null;
        $faviconVersion = !empty($companySettings?->updated_at) ? $companySettings->updated_at->timestamp : null;
        $faviconUrl = $faviconPath
            ? \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($faviconPath)
            : null;
        if ($faviconUrl && $faviconVersion) {
            $faviconUrl .= '?v=' . $faviconVersion;
        }
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
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $appTitle !== '' ? $appTitle : 'VOYEX CRM' }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    @if ($faviconUrl)
        <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @else
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
    @endif
    @vite(['resources/css/app.css','resources/js/app.js'])
    @stack('styles')
</head>

<body class="app-shell bg-gray-100 dark:bg-gray-900 transition-colors duration-300" data-currency="{{ $currentCurrency ?? 'IDR' }}">
<div class="page-spinner" data-page-spinner aria-hidden="true">
    <div class="page-spinner__inner">
        <div class="page-spinner__ring" aria-hidden="true"></div>
        <div class="page-spinner__text">Loading...</div>
    </div>
</div>

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside  class="fixed inset-y-0 left-0 z-40 bg-primary text-white transform transition-all duration-300
                  w-64 md:static md:translate-x-0 md:flex-shrink-0 overflow-y-auto max-h-screen" :class="{
                      'translate-x-0': sidebarOpen,
                      '-translate-x-full md:translate-x-0': !sidebarOpen,
                      'md:w-20 sidebar-is-collapsed': sidebarCollapsed,
                      'md:w-64': !sidebarCollapsed
                  }">

        <div class="p-4 border-b border-gray-700 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0 overflow-hidden">
                @if ($logoUrl)
                    <img
                        src="{{ $logoUrl }}"
                        alt="{{ $appTitle !== '' ? $appTitle : 'VOYEX CRM' }} Logo"
                        class="h-8 w-8 rounded-lg object-cover border border-white/20 shrink-0"
                    >
                @endif
                <div class="text-xl font-bold whitespace-nowrap overflow-hidden"
                     :class="sidebarCollapsed ? 'md:hidden' : 'block'">
                    {{ $appTitle !== '' ? $appTitle : 'VOYEX CRM' }}
                </div>
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
    <div class="flex-1 flex flex-col min-h-0">

        <header class="sticky top-0 z-20 bg-white/95 dark:bg-gray-800/95 backdrop-blur shadow-sm px-3 py-3 sm:px-4 sm:py-3 md:px-6 md:py-4 flex items-center justify-between gap-3">
            <!-- mobile-only button -->
            <button @click="sidebarOpen = !sidebarOpen"  class="md:hidden inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="hidden md:flex items-center gap-2 min-w-0 overflow-x-auto">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 whitespace-nowrap">Rates</span>
                @forelse (($currencyOptions ?? collect())->filter(fn ($c) => strtoupper((string) ($c->code ?? '')) !== 'IDR') as $currencyRate)
                    @php
                        $rateValue = (float) ($currencyRate->rate_to_idr ?? 1);
                        $rateLabel = number_format($rateValue, 0, ',', '.');
                    @endphp
                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[11px] font-medium whitespace-nowrap {{ ($currentCurrency ?? 'IDR') === $currencyRate->code ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                        <span class="font-semibold">{{ strtoupper((string) $currencyRate->code) }}</span>
                        <span>=</span>
                        <span>{{ $rateLabel }} IDR</span>
                    </span>
                @empty
                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2 py-1 text-[11px] text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        No non-IDR rates
                    </span>
                @endforelse
            </div>

            <div class="ml-auto flex items-center gap-3 sm:gap-4 md:gap-6 min-w-0">
                @php
                    $approvalNotif = is_array($quotationApprovalNotification ?? null) ? $quotationApprovalNotification : ['visible' => false, 'count' => 0, 'role' => null];
                    $notifCount = (int) ($approvalNotif['count'] ?? 0);
                    $notifRole = (string) ($approvalNotif['role'] ?? '');
                    $notifTitle = $notifRole !== ''
                        ? ('Quotation approvals pending for ' . ucfirst($notifRole))
                        : 'Quotation approvals pending';
                    $notifClass = match ($notifRole) {
                        'director' => 'border-violet-300 bg-violet-50 text-violet-700 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-900/20 dark:text-violet-300 dark:hover:bg-violet-900/30',
                        'manager' => 'border-sky-300 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300 dark:hover:bg-sky-900/30',
                        default => 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/30',
                    };
                @endphp

                @if (($approvalNotif['visible'] ?? false))
                    <a
                        href="{{ route('quotations.index', ['status' => 'pending', 'needs_my_approval' => 1]) }}"
                        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border {{ $notifClass }} {{ $notifCount > 0 ? '' : 'hidden' }}"
                        title="{{ $notifTitle }}"
                        aria-label="{{ $notifTitle }}"
                        data-quotation-approval-bell="1"
                    >
                        <i class="fa-solid fa-bell"></i>
                        <span class="absolute -right-1.5 -top-1.5 inline-flex min-w-[18px] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white" data-quotation-approval-count="1">
                            {{ $notifCount > 99 ? '99+' : $notifCount }}
                        </span>
                    </a>
                    <span
                        class="hidden"
                        data-quotation-approval-notifier="1"
                        data-endpoint="{{ route('quotations.approval-notifications.poll') }}"
                        data-role="{{ $notifRole }}"
                        data-user-id="{{ auth()->id() }}"
                    ></span>
                @endif

                <!-- Currency Switch -->
                <div class="hidden sm:flex items-center gap-2">
                    <form method="POST" action="{{ route('currency.set') }}">
                        @csrf
                        <div class="relative">
                            <select
                                name="currency"
                                onchange="this.form.submit()"
                                class="nav-currency-select h-9 font-semibold uppercase tracking-wide text-gray-700 transition hover:border-indigo-300 dark:border-gray-700 dark:hover:border-indigo-600 app-input"
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
                        <a href="{{ route('currencies.index') }}"  class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:text-gray-200">
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

                        <a href="{{ route('profile.edit') }}"  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Profile
                        </a>
                        @can('module.quotations.access')
                            @if (Route::has('quotations.my'))
                                <a href="{{ route('quotations.my') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    My Quotations
                                </a>
                            @endif
                        @endcan

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"  class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Logout
                            </button>
                        </form>

                    </div>
                </div>

            </div>

        </header>

        <!-- PAGE CONTENT -->
        <main class="app-content flex-1 min-h-0 overflow-y-auto px-3 py-3 sm:px-4 sm:py-4 lg:px-5 xl:px-6">
            <div class="app-page-shell">
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
            </div>
        </main>

    </div>
</div>

<div id="pwa-install-banner" class="pwa-install-banner hidden" role="dialog" aria-live="polite" aria-label="Install app banner">
    <div class="pwa-install-banner__card">
        <div class="pwa-install-banner__header">
            <p class="pwa-install-banner__title">Install Aplikasi</p>
            <button type="button" id="pwa-install-close" class="pwa-install-banner__close" aria-label="Tutup banner install">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <p id="pwa-install-message" class="pwa-install-banner__message"></p>
        <div class="pwa-install-banner__actions">
            <button type="button" id="pwa-install-action" class="btn-primary-sm hidden">Install Sekarang</button>
            <button type="button" id="pwa-install-later" class="btn-ghost-sm">Nanti</button>
        </div>
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
        $currencySymbol = is_array($currencyMeta) ? (string) ($currencyMeta['symbol'] ?? '') : '';
        if ($currencySymbol === '') {
            $currencySymbol = ($currentCurrency ?? 'IDR') === 'USD' ? '$' : 'Rp';
        }
    @endphp
    window.appCurrency = @json($currentCurrency ?? 'IDR');
    window.appCurrencyRateToIdr = @json($currencyRateToIdr);
    window.appCurrencyDecimals = @json($currencyDecimals);
    window.appCurrencySymbol = @json($currencySymbol);

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
        const moneyPattern = /(contract_rate|publish_rate|unit_price|overtime_rate|optional_rate|kick_back|markup|price|amount|fee|cost|discount|total|final_amount|sub_total)/i;
        const fields = root.querySelectorAll('input[data-money-input="1"], input[type="number"], input[inputmode="decimal"], input[inputmode="numeric"], input[type="text"]');
        const currencyBadgeText = window.appCurrencySymbol || window.appCurrency || 'IDR';

        const ensureLeftAffixWrapper = (field) => {
            if (!(field instanceof HTMLInputElement)) {
                return null;
            }

            const existingWrapper = field.closest('.input-with-left-affix');
            if (existingWrapper) {
                let existingBadge = existingWrapper.querySelector('[data-money-badge="1"]');
                if (!existingBadge) {
                    existingBadge = document.createElement('span');
                    existingBadge.className = 'input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                    existingBadge.setAttribute('data-money-badge', '1');
                    existingBadge.setAttribute('data-money-badge-default', currencyBadgeText);
                    existingBadge.textContent = currencyBadgeText;
                    existingWrapper.appendChild(existingBadge);
                }
                return existingBadge;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'input-with-left-affix';

            const parent = field.parentElement;
            if (!parent) {
                return null;
            }

            parent.insertBefore(wrapper, field);
            wrapper.appendChild(field);

            const badge = document.createElement('span');
            badge.className = 'input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
            badge.setAttribute('data-money-badge', '1');
            badge.setAttribute('data-money-badge-default', currencyBadgeText);
            badge.textContent = currencyBadgeText;
            wrapper.appendChild(badge);

            return badge;
        };

        const findRelatedMarkupTypeSelect = (field) => {
            if (!(field instanceof HTMLInputElement)) {
                return null;
            }

            const fieldName = String(field.getAttribute('name') || '').trim();
            const isMarkupField = /\bmarkup\b/i.test(fieldName) || field.dataset.hotelRate === 'markup';
            if (!isMarkupField) {
                return null;
            }

            const candidates = [];
            if (fieldName.includes('[markup]')) {
                candidates.push(fieldName.replace('[markup]', '[markup_type]'));
            }
            if (/_markup$/i.test(fieldName)) {
                candidates.push(fieldName.replace(/_markup$/i, '_markup_type'));
            }
            if (/\bmarkup$/i.test(fieldName)) {
                candidates.push(fieldName.replace(/\bmarkup$/i, 'markup_type'));
            }

            const form = field.closest('form');
            if (form && typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
                for (const candidate of candidates) {
                    const select = form.querySelector(`select[name="${CSS.escape(candidate)}"]`);
                    if (select) {
                        return select;
                    }
                }
            }

            const rowScope = field.closest('[data-row], tr, .grid, .space-y-3, .space-y-4');
            if (rowScope) {
                const rowSelect = rowScope.querySelector('[data-hotel-rate="markup_type"], select[name*="markup_type"]');
                if (rowSelect) {
                    return rowSelect;
                }
            }

            return null;
        };

        const toIdrInteger = (value) => {
            const raw = String(value ?? '').trim();
            if (raw === '') {
                return null;
            }

            // Case 1: pure decimal from backend (e.g. "1000000.00")
            if (/^\d+([.,]\d{1,2})?$/.test(raw) && !raw.includes(' ')) {
                const numeric = Number(raw.replace(',', '.'));
                if (Number.isFinite(numeric)) {
                    return Math.round(numeric);
                }
            }

            // Case 2: grouped user input (e.g. "1.000.000")
            const digits = raw.replace(/[^\d]/g, '');
            if (digits === '') {
                return null;
            }
            return Number(digits);
        };

        const toIdrGrouped = (value) => {
            const integerValue = toIdrInteger(value);
            if (integerValue === null || !Number.isFinite(integerValue)) {
                return '';
            }
            return new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0,
            }).format(integerValue);
        };

        fields.forEach((field) => {
            if (field.dataset.moneyHintBound === '1') {
                return;
            }

            const name = (field.getAttribute('name') || field.id || '').trim();
            const explicitMoneyInput = field.dataset.moneyInput === '1';
            const looksLikeMoneyField = name && moneyPattern.test(name);
            if (!explicitMoneyInput && !looksLikeMoneyField) {
                return;
            }

            field.dataset.moneyHintBound = '1';
            field.dataset.moneyFormatBound = '1';
            field.dataset.moneyCurrency = String(window.appCurrency || 'IDR').toUpperCase();
            field.classList.add('pl-14', 'text-right');
            field.setAttribute('inputmode', 'numeric');
            field.setAttribute('autocomplete', 'off');

            if (field.type === 'number') {
                field.type = 'text';
            }

            const badge = ensureLeftAffixWrapper(field);
            const markupTypeSelect = findRelatedMarkupTypeSelect(field);
            const syncBadge = () => {
                if (!badge) {
                    return;
                }
                const isPercent = markupTypeSelect && String(markupTypeSelect.value || 'fixed').toLowerCase() === 'percent';
                badge.textContent = isPercent ? '%' : currencyBadgeText;
            };

            const applyFormat = () => {
                field.value = toIdrGrouped(field.value);
            };

            if (field.value) {
                applyFormat();
            }
            syncBadge();

            field.addEventListener('input', applyFormat);
            field.addEventListener('change', applyFormat);
            if (markupTypeSelect && markupTypeSelect.dataset.moneyBadgeBound !== '1') {
                markupTypeSelect.dataset.moneyBadgeBound = '1';
                markupTypeSelect.addEventListener('change', () => {
                    syncBadge();
                });
            }

            // Global hint intentionally disabled: all money inputs already enforce IDR formatting.
        });
    }

    function normalizeMoneyInputsBeforeSubmit() {
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            form.querySelectorAll('input[data-money-format-bound="1"]').forEach((field) => {
                if (!(field instanceof HTMLInputElement)) {
                    return;
                }
                field.value = String(field.value ?? '').replace(/[^\d]/g, '');
            });
        }, true);
    }

    document.addEventListener('DOMContentLoaded', () => {
        normalizeMoneyInputsBeforeSubmit();
        attachRequiredMarkers(document);
        attachMoneyHints(document);
        enhanceAddButtons(document);
        enhanceRemoveButtons(document);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        attachRequiredMarkers(node);
                        attachMoneyHints(node);
                        enhanceAddButtons(node);
                        enhanceRemoveButtons(node);
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

            let resolveTimer = null;
            const scheduleResolve = () => {
                if (resolveTimer) {
                    clearTimeout(resolveTimer);
                }
                resolveTimer = setTimeout(resolveLocation, 400);
            };

            const scheduleResolveFromUserInput = (event) => {
                if (event && event.isTrusted !== true) {
                    return;
                }
                scheduleResolve();
            };

            const resolveLocationFromUserInput = (event) => {
                if (event && event.isTrusted !== true) {
                    return;
                }
                resolveLocation();
            };

            urlInput.addEventListener('input', scheduleResolveFromUserInput);
            urlInput.addEventListener('change', resolveLocationFromUserInput);
            urlInput.addEventListener('blur', resolveLocationFromUserInput);
            if (triggerButton) {
                triggerButton.addEventListener('click', resolveLocation);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initLocationAutofill(document);
        enhanceAddButtons(document);
        enhanceRemoveButtons(document);
        localizeTimes(document);
        initQuotationApprovalNotifier();

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        initLocationAutofill(node);
                        enhanceAddButtons(node);
                        enhanceRemoveButtons(node);
                        localizeTimes(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });

    function enhanceAddButtons(root = document) {
        const nodes = root.querySelectorAll('a, button');
        nodes.forEach((node) => {
            if (node.dataset.addStyled === '1' || node.dataset.skipAddStyle === '1') {
                return;
            }

            const label = (node.textContent || '').trim();
            if (!/^(add|tambah)\b/i.test(label)) {
                return;
            }

            const classList = node.classList;
            const isSmall = Array.from(classList).some((item) => item.endsWith('-sm'));
            const removeList = [
                'btn-ghost', 'btn-outline', 'btn-secondary', 'btn-muted', 'btn-danger', 'btn-warning',
                'btn-ghost-sm', 'btn-outline-sm', 'btn-secondary-sm', 'btn-muted-sm', 'btn-danger-sm', 'btn-warning-sm',
            ];
            removeList.forEach((cls) => classList.remove(cls));
            classList.add(isSmall ? 'btn-primary-sm' : 'btn-primary');

            if (!node.querySelector('.btn-add-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-plus btn-add-icon';
                node.insertAdjacentElement('afterbegin', icon);
            }

            node.dataset.addStyled = '1';
        });
    }

    function enhanceRemoveButtons(root = document) {
        const nodes = root.querySelectorAll('a, button');
        nodes.forEach((node) => {
            if (node.dataset.removeStyled === '1' || node.dataset.skipRemoveStyle === '1') {
                return;
            }

            if (node.closest('table') || node.closest('.app-table')) {
                return;
            }

            const label = (node.textContent || '').trim();
            if (!/^(remove|hapus)\b/i.test(label)) {
                return;
            }

            const classList = node.classList;
            const isSmall = Array.from(classList).some((item) => item.endsWith('-sm'));
            const removeList = [
                'btn-ghost', 'btn-outline', 'btn-secondary', 'btn-muted', 'btn-warning',
                'btn-ghost-sm', 'btn-outline-sm', 'btn-secondary-sm', 'btn-muted-sm', 'btn-warning-sm',
            ];
            removeList.forEach((cls) => classList.remove(cls));
            classList.add(isSmall ? 'btn-danger-sm' : 'btn-danger');

            if (!node.querySelector('.btn-remove-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-trash btn-remove-icon';
                node.insertAdjacentElement('afterbegin', icon);
            }

            node.dataset.removeStyled = '1';
        });
    }

    function localizeTimes(root = document) {
        const nodes = root.querySelectorAll('[data-local-time="1"]');
        if (!nodes.length) {
            return;
        }

        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        nodes.forEach((node) => {
            const iso = node.getAttribute('datetime');
            if (!iso) return;

            const parsed = new Date(iso);
            if (Number.isNaN(parsed.getTime())) return;

            const showTimezone = node.hasAttribute('data-local-timezone');
            const parts = new Intl.DateTimeFormat('en-CA', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
                timeZone: timezone,
            }).formatToParts(parsed);
            const map = Object.fromEntries(parts.map((part) => [part.type, part.value]));
            const formatted = `${map.year}-${map.month}-${map.day} (${map.hour}:${map.minute})`;

            if (showTimezone) {
                const tzName = new Intl.DateTimeFormat('en', {
                    timeZone: timezone,
                    timeZoneName: 'short',
                }).formatToParts(parsed).find((part) => part.type === 'timeZoneName')?.value;
                node.textContent = tzName ? `${formatted} ${tzName}` : formatted;
            } else {
                node.textContent = formatted;
            }
            node.setAttribute('title', `${iso} UTC`);
        });
    }

    function initQuotationApprovalNotifier() {
        const configNode = document.querySelector('[data-quotation-approval-notifier="1"]');
        if (!configNode || configNode.dataset.bound === '1') {
            return;
        }
        configNode.dataset.bound = '1';

        const endpoint = configNode.getAttribute('data-endpoint');
        const role = configNode.getAttribute('data-role') || 'unknown';
        const userId = configNode.getAttribute('data-user-id') || '0';
        const bellNode = document.querySelector('[data-quotation-approval-bell="1"]');
        const countNode = bellNode ? bellNode.querySelector('[data-quotation-approval-count="1"]') : null;
        const listUrl = `{{ route('quotations.index', ['status' => 'pending', 'needs_my_approval' => 1]) }}`;
        const storageKey = `quotation_approval_latest_${userId}_${role}`;

        if (!endpoint || !bellNode || !countNode) {
            return;
        }

        let bootstrapped = false;
        const baseTitle = document.title;
        const titlePrefixPattern = /^\(\d+\)\s+/;
        const faviconLinks = Array.from(document.querySelectorAll('link[rel~="icon"], link[rel="shortcut icon"], link[rel="apple-touch-icon"]'));
        const faviconBaseMap = new Map();
        faviconLinks.forEach((link) => {
            const href = link.getAttribute('href') || '';
            faviconBaseMap.set(link, href);
        });

        const setTabTitleCount = (count) => {
            const safeCount = Number.isFinite(Number(count)) ? Math.max(0, Number(count)) : 0;
            const cleanBaseTitle = String(baseTitle || document.title || '').replace(titlePrefixPattern, '');
            if (safeCount > 0) {
                document.title = `(${safeCount}) ${cleanBaseTitle}`;
                return;
            }
            document.title = cleanBaseTitle;
        };

        const buildFaviconBadgeDataUrl = (sourceHref, count) => new Promise((resolve) => {
            const safeCount = Number.isFinite(Number(count)) ? Math.max(0, Number(count)) : 0;
            if (safeCount <= 0 || !sourceHref) {
                resolve(sourceHref || '');
                return;
            }

            const image = new Image();
            image.crossOrigin = 'anonymous';
            image.onload = () => {
                try {
                    const size = 64;
                    const canvas = document.createElement('canvas');
                    canvas.width = size;
                    canvas.height = size;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        resolve(sourceHref);
                        return;
                    }

                    ctx.drawImage(image, 0, 0, size, size);
                    ctx.fillStyle = '#dc2626';
                    ctx.beginPath();
                    ctx.arc(49, 15, 14, 0, Math.PI * 2);
                    ctx.fill();

                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 16px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    const label = safeCount > 99 ? '99+' : String(safeCount);
                    ctx.fillText(label, 49, 15);

                    resolve(canvas.toDataURL('image/png'));
                } catch (_) {
                    resolve(sourceHref);
                }
            };
            image.onerror = () => resolve(sourceHref);
            image.src = sourceHref;
        });

        const setFaviconCount = async (count) => {
            const safeCount = Number.isFinite(Number(count)) ? Math.max(0, Number(count)) : 0;
            for (const link of faviconLinks) {
                const baseHref = faviconBaseMap.get(link) || '';
                if (safeCount <= 0) {
                    link.setAttribute('href', baseHref);
                    continue;
                }
                const dataUrl = await buildFaviconBadgeDataUrl(baseHref, safeCount);
                link.setAttribute('href', dataUrl || baseHref);
            }
        };

        const setBellCount = (count) => {
            const safeCount = Number.isFinite(Number(count)) ? Math.max(0, Number(count)) : 0;
            setTabTitleCount(safeCount);
            setFaviconCount(safeCount);
            if (safeCount > 0) {
                bellNode.classList.remove('hidden');
                countNode.textContent = safeCount > 99 ? '99+' : String(safeCount);
            } else {
                bellNode.classList.add('hidden');
                countNode.textContent = '0';
            }
        };

        const showPopupNotification = (quotationNumber = '') => {
            const playNotificationTone = () => {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) {
                        return;
                    }
                    const context = new AudioCtx();
                    const oscillator = context.createOscillator();
                    const gainNode = context.createGain();

                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(880, context.currentTime);
                    gainNode.gain.setValueAtTime(0.0001, context.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.12, context.currentTime + 0.02);
                    gainNode.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.35);

                    oscillator.connect(gainNode);
                    gainNode.connect(context.destination);
                    oscillator.start();
                    oscillator.stop(context.currentTime + 0.36);
                } catch (_) {
                    // Ignore audio runtime error.
                }
            };

            if (!('Notification' in window)) {
                playNotificationTone();
                return;
            }

            const title = 'New quotation needs approval';
            const body = quotationNumber
                ? `Quotation ${quotationNumber} requires your approval.`
                : 'A new quotation requires your approval.';

            const trigger = () => {
                try {
                    const notification = new Notification(title, {
                        body,
                        icon: '/favicon.ico',
                    });
                    notification.onclick = () => {
                        window.focus();
                        window.location.href = listUrl;
                    };
                } catch (_) {
                    // Ignore notification API runtime error.
                }
            };

            if (Notification.permission === 'granted') {
                playNotificationTone();
                trigger();
                return;
            }

            if (Notification.permission === 'default') {
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        playNotificationTone();
                        trigger();
                    }
                }).catch(() => {});
            }
        };

        const poll = async () => {
            try {
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    return;
                }
                const payload = await response.json();
                if (!payload || payload.enabled !== true) {
                    setBellCount(0);
                    return;
                }

                const count = Number(payload.count || 0);
                setBellCount(count);

                const latestId = payload.latest && payload.latest.id ? String(payload.latest.id) : '';
                const latestNumber = payload.latest && payload.latest.quotation_number
                    ? String(payload.latest.quotation_number)
                    : '';
                const previousLatestId = sessionStorage.getItem(storageKey) || '';

                if (!bootstrapped) {
                    if (latestId) {
                        sessionStorage.setItem(storageKey, latestId);
                    }
                    bootstrapped = true;
                    return;
                }

                if (latestId && latestId !== previousLatestId) {
                    sessionStorage.setItem(storageKey, latestId);
                    showPopupNotification(latestNumber);
                }
            } catch (_) {
                // Ignore polling network error.
            }
        };

        poll();
        window.setInterval(poll, 20000);
    }
</script>
<script>
    (function () {
        const storageDismissKey = 'pwa_install_banner_dismissed';
        const banner = document.getElementById('pwa-install-banner');
        const messageNode = document.getElementById('pwa-install-message');
        const actionBtn = document.getElementById('pwa-install-action');
        const closeBtn = document.getElementById('pwa-install-close');
        const laterBtn = document.getElementById('pwa-install-later');
        let deferredInstallPrompt = null;

        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (isStandalone) {
            document.documentElement.classList.add('app-standalone');
            document.body.classList.add('app-standalone');
        }

        const isMobileViewport = window.matchMedia('(max-width: 1024px)').matches;
        const ua = window.navigator.userAgent || '';
        const isIOS = /iPhone|iPad|iPod/i.test(ua);
        const isAndroid = /Android/i.test(ua);
        const wasDismissed = window.localStorage.getItem(storageDismissKey) === '1';

        const hideBanner = (persist = false) => {
            if (!banner) return;
            banner.classList.add('hidden');
            if (persist) {
                window.localStorage.setItem(storageDismissKey, '1');
            }
        };

        const tryEnterFullscreen = async () => {
            if (isStandalone || !isMobileViewport || document.fullscreenElement) {
                return;
            }

            const root = document.documentElement;
            const requestFullscreen = root.requestFullscreen
                || root.webkitRequestFullscreen
                || root.msRequestFullscreen;

            if (!requestFullscreen) {
                return;
            }

            try {
                await requestFullscreen.call(root);
            } catch (_) {
                // Ignore fullscreen rejection in restricted browsers.
            }
        };

        const bindAutoFullscreenAttempt = () => {
            if (isStandalone || !isMobileViewport) {
                return;
            }

            let attempted = false;
            const autoAttempt = () => {
                if (attempted) return;
                attempted = true;
                tryEnterFullscreen();
                window.removeEventListener('touchend', autoAttempt, true);
                window.removeEventListener('click', autoAttempt, true);
                window.removeEventListener('keydown', autoAttempt, true);
            };

            window.addEventListener('touchend', autoAttempt, true);
            window.addEventListener('click', autoAttempt, true);
            window.addEventListener('keydown', autoAttempt, true);
        };

        const showBanner = () => {
            if (!banner || !isMobileViewport || isStandalone || wasDismissed) {
                return;
            }
            banner.classList.remove('hidden');
        };

        const setBannerContent = (message, showActionButton) => {
            if (!messageNode || !actionBtn) return;
            messageNode.textContent = message;
            actionBtn.classList.toggle('hidden', !showActionButton);
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', () => hideBanner(true));
        }

        if (laterBtn) {
            laterBtn.addEventListener('click', () => hideBanner(true));
        }

        if (actionBtn) {
            actionBtn.addEventListener('click', async () => {
                if (!deferredInstallPrompt) return;
                deferredInstallPrompt.prompt();
                try {
                    await deferredInstallPrompt.userChoice;
                } catch (_) {
                    // Ignore install prompt runtime error.
                }
                deferredInstallPrompt = null;
                hideBanner(true);
            });
        }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredInstallPrompt = event;
            setBannerContent('Install aplikasi ini ke Home Screen agar tampil fullscreen seperti mobile app.', true);
            showBanner();
        });

        window.addEventListener('appinstalled', () => {
            hideBanner(true);
        });

        if (!isStandalone && !wasDismissed && isMobileViewport) {
            if (isIOS) {
                setBannerContent('Di iPhone/iPad: ketuk Share lalu pilih "Add to Home Screen" untuk mode fullscreen.', false);
                showBanner();
            } else if (isAndroid) {
                setBannerContent('Gunakan menu browser lalu pilih "Install app" atau "Add to Home screen".', false);
                showBanner();
            }
        }

        bindAutoFullscreenAttempt();

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('service-worker.js') }}').catch(function () {
                    // Ignore registration error in unsupported/restricted environments.
                });
            });
        }
    })();
</script>
@stack('scripts')

</body>
</html>
