@extends('layouts.master')

@section('content')
    @php
        // Prepare chart and display data
        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        $monthlyMap = $monthlyData->toArray();
        $chartLabels = [];
        $chartValues = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartLabels[] = $monthNames[$i];
            $chartValues[] = $monthlyMap[$i] ?? 0;
        }
    @endphp

    <!-- Header Dashboard -->
    @section('page_title', $dashboardTitle ?? 'Administrator Dashboard')
    @section('page_subtitle', $dashboardSubtitle ?? "Welcome back, ".auth()->user()->name.". Here's your performance overview.")
    @section('page_actions')
        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ \Carbon\Carbon::now()->format('l, j F Y') }}</span>
    @endsection

    @if (! ($isEditor ?? false))
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-8">
            @if($canUsers || $canServices)
            <div class="xl:col-span-7 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Company Governance</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kontrol user internal dan status modul operasional perusahaan.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ ($companyProfileReady ?? false) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                        {{ ($companyProfileReady ?? false) ? 'Company Profile Ready' : 'Company Profile Incomplete' }}
                    </span>
                </div>
                @if($canUsers)
                <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Users</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($teamStats['total_users'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sales Team</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($teamStats['sales_team'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Reservation</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($teamStats['operations'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Finance</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($teamStats['finance'] ?? 0) }}</p>
                    </div>
                </div>
                @endif
                @if($canServices)
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Managed Modules</p>
                        <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ number_format($moduleGovernance['visible'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Enabled</p>
                        <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($moduleGovernance['enabled'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Disabled</p>
                        <p class="text-lg font-semibold text-rose-600 dark:text-rose-400">{{ number_format($moduleGovernance['disabled'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="xl:col-span-5 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Quick Actions</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Akses cepat untuk aktivitas administrasi perusahaan.</p>
                <div class="mt-4 grid grid-cols-1 gap-2 text-sm">
                    @if(auth()->user()->can('company_settings.manage'))
                        <a href="{{ route('company-settings.edit') }}"  class="btn-primary">Company Settings</a>
                    @endif
                    @if(auth()->user()->can('module.user_manager.access') && Route::has('users.index'))
                        <a href="{{ route('users.index') }}"  class="btn-primary">Manage Users</a>
                    @endif
                    @if(auth()->user()->can('module.service_manager.access') && Route::has('services.index'))
                        <a href="{{ route('services.index') }}"  class="btn-primary">Module Management</a>
                    @endif
                    @if(auth()->user()->can('module.role_manager.access') && Route::has('roles.index'))
                        <a href="{{ route('roles.index') }}"  class="btn-primary">Role & Permissions</a>
                    @endif
                </div>
            </div>
        </div>

        @if($canServices)
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700 mb-8">
            @php
                $moduleRoutes = [
                    'customer_management' => 'customers.index',
                    'inquiries' => 'inquiries.index',
                    'itineraries' => 'itineraries.index',
                    'quotations' => 'quotations.index',
                    'bookings' => 'bookings.index',
                    'invoices' => 'invoices.index',
                    'vendor_management' => 'vendors.index',
                    'destinations' => 'destinations.index',
                    'activities' => 'activities.index',
                    'airports' => 'airports.index',
                    'transports' => 'transports.index',
                    'tourist_attractions' => 'tourist-attractions.index',
                    'user_manager' => 'users.index',
                ];
            @endphp
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Accessible Modules</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                @forelse(($managedModules ?? []) as $module)
                    @if($module['can_access'])
                        @php
                            $routeName = $moduleRoutes[$module['key']] ?? null;
                            $moduleUrl = null;
                            if (! ($module['is_enabled'] ?? false) && Route::has('services.index')) {
                                $moduleUrl = route('services.index');
                            } elseif ($routeName && Route::has($routeName)) {
                                $moduleUrl = route($routeName);
                            }
                        @endphp
                        @if($moduleUrl)
                            <a href="{{ $moduleUrl }}" class="block rounded-xl border border-gray-200 dark:border-gray-700 p-3 transition hover:bg-gray-50 dark:hover:bg-gray-900 hover:shadow">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm">{{ $module['name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $module['key'] }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ ($module['is_enabled'] ?? false) ? 'Open' : 'Fix' }}</span>
                                </div>
                                <span class="inline-flex mt-2 rounded-full px-2.5 py-1 text-xs font-semibold {{ $module['is_enabled'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                    {{ $module['is_enabled'] ? 'Enabled' : 'Disabled' }}
                                </span>
                            </a>
                        @else
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm">{{ $module['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $module['key'] }}</p>
                                <span class="inline-flex mt-2 rounded-full px-2.5 py-1 text-xs font-semibold {{ $module['is_enabled'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                    {{ $module['is_enabled'] ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        @endif
                    @endif
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">No accessible modules.</div>
                @endforelse
            </div>
        </div>
        @endif
    @endif

    <!-- Main KPI Grid -->
    <div class="dashboard-kpi-grid">
        <!-- Monthly Revenue -->
        @if($canBookings)
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monthly Revenue</p>
                    <p class="text-3xl font-bold text-primary mt-2"><x-money :amount="$monthlyRevenue ?? 0" currency="IDR" /></p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/50 text-primary p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 6v2m0 8v2m-6-4h.01M18 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>
        @endif

        <!-- Conversion Rate -->
        @if($canBookings && $canInquiries)
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conversion Rate</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ number_format($conversionRate, 2) }}%</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
            </div>
        </div>
        @endif

    </div>

    <!-- Chart and List -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Revenue Chart -->
        @if($canBookings)
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Revenue Trend</h2>
                <span class="bg-blue-100 text-primary text-xs font-medium px-3 py-1 rounded-full">This Year</span>
            </div>
            <canvas id="revenueChart" class="max-h-80"></canvas>
        </div>
        @endif

        <!-- Deadline Quotations -->
        @if($canQuotations)
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Expiring Quotations</h3>
            <div class="space-y-4 max-h-80 overflow-y-auto">
                @forelse ($deadlineQuotations as $q)
                    <div class="flex items-center">
                        <div class="bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 p-2 rounded-full mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-200">{{ $q->quotation_number }}</p>
                            <p class="text-sm text-red-500">Expires: {{ \Carbon\Carbon::parse($q->validity_date)->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-gray-500">No expiring quotations.</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif
    </div>

    <!-- Upcoming Bookings -->
    @if($canBookings)
    <div class="mt-8 app-card p-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Upcoming Bookings</h3>
        <div class="overflow-x-auto">
            <table class="app-table w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Booking ID</th>
                        <th scope="col" class="px-6 py-3">Travel Date</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($upcomingBookings as $b)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $b->booking_number }}
                        </th>
                        <td class="px-6 py-4">
                            {{ \Carbon\Carbon::parse($b->travel_date)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                {{ ucfirst($b->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-8 text-gray-500">
                            No upcoming bookings found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @push('scripts')
    @if($canBookings)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = @json($chartLabels);
        const values = @json($chartValues);
        const canvas = document.getElementById('revenueChart');
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.4)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue',
                    data: values,
                    borderColor: '#2563eb', // primary color
                    backgroundColor: gradient,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBorderColor: '#2563eb',
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#2563eb',
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    const currency = window.appCurrency || 'IDR';
                                    const rate = window.appCurrencyRateToIdr || 1;
                                    const value = currency === 'IDR' ? context.parsed.y : (context.parsed.y / rate);
                                    label += new Intl.NumberFormat(currency === 'USD' ? 'en-US' : 'id-ID', {
                                        style: 'currency',
                                        currency: currency,
                                        minimumFractionDigits: window.appCurrencyDecimals ?? 0,
                                        maximumFractionDigits: window.appCurrencyDecimals ?? 0
                                    }).format(value);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                const currency = window.appCurrency || 'IDR';
                                const rate = window.appCurrencyRateToIdr || 1;
                                const val = currency === 'IDR' ? value : (value / rate);
                                return new Intl.NumberFormat(currency === 'USD' ? 'en-US' : 'id-ID', {
                                    style: 'currency',
                                    currency: currency,
                                    notation: 'compact',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 1
                                }).format(val);
                            }
                        },
                        grid: {
                            color: document.body.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                    }
                }
            }
        });
    </script>
    @endif
    @endpush
@endsection


