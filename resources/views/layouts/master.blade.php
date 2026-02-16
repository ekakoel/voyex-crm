<!DOCTYPE html>
<html lang="en" x-data="siteData()" :class="dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VOYEX CRM</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300">

<style>
    .sidebar-is-collapsed .sidebar-label,
    .sidebar-is-collapsed .sidebar-arrow {
        display: none;
    }
</style>

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="fixed inset-y-0 left-0 z-40 bg-primary text-white transform transition-all duration-300
                  w-64 md:static md:translate-x-0 md:flex-shrink-0" :class="{
                      'translate-x-0': sidebarOpen,
                      '-translate-x-full md:translate-x-0': !sidebarOpen,
                      'md:w-20 sidebar-is-collapsed': sidebarCollapsed,
                      'md:w-64': !sidebarCollapsed
                  }">

        <div class="p-4 border-b border-gray-700 flex items-center justify-between gap-2">
            <div class="text-xl font-bold whitespace-nowrap overflow-hidden"
                 :class="sidebarCollapsed ? 'md:hidden' : 'block'">
                VOYEX CRM
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
                    @php
                        $hasChildren = ! empty($item['children']) && is_array($item['children']);
                        $isItemActive = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
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
                                    class="w-full group flex items-center gap-3 px-4 py-2 rounded-lg transition-colors duration-200 text-left text-white/90 hover:text-white
                                           {{ $isChildActive ? 'bg-gray-700 font-semibold' : 'hover:bg-gray-700' }}"
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
                                               class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-white/85 hover:text-white transition-colors duration-200
                                                      {{ request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*') ? 'bg-gray-700/80 font-semibold' : 'hover:bg-gray-700/70' }}">
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
                               class="group flex items-center gap-3 px-4 py-2 rounded-lg transition-colors duration-200 text-white/90 hover:text-white
                                      {{ $isItemActive ? 'bg-gray-700 font-semibold' : 'hover:bg-gray-700' }}"
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

        <header class="bg-white dark:bg-gray-800 shadow px-6 py-5 flex items-center justify-between">
            <!-- mobile-only button -->
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-600 dark:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center gap-6">

                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()"
                        class="transition-colors duration-200"
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
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                        <span class="truncate max-w-[150px]">{{ auth()->user()->name }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>

                    <div x-show="open"
                         @click.outside="open = false"
                         x-cloak class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border dark:border-gray-700 py-1 z-10">

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
        <main class="flex-1 p-4 sm:p-6 overflow-y-auto">
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
</script>

</body>
</html>
