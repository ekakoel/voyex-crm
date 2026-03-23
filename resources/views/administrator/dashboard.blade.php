@extends('layouts.master')

@section('content')
@php
    $systemKpis = [];
    if ($canUsers) {
        $systemKpis[] = ['label' => 'Users', 'value' => $systemManagementStats['users'] ?? 0, 'icon' => 'users', 'color' => 'sky', 'route' => 'users.index'];
    }
    if ($canRoles) {
        $systemKpis[] = ['label' => 'Roles', 'value' => $systemManagementStats['roles'] ?? 0, 'icon' => 'user-shield', 'color' => 'violet', 'route' => 'roles.index'];
    }
    if ($canServices) {
        $systemKpis[] = ['label' => 'Modules On', 'value' => $systemManagementStats['modules_enabled'] ?? 0, 'icon' => 'toggle-on', 'color' => 'emerald', 'route' => 'services.index'];
        $systemKpis[] = ['label' => 'Modules Off', 'value' => $systemManagementStats['modules_disabled'] ?? 0, 'icon' => 'toggle-off', 'color' => 'rose', 'route' => 'services.index'];
    }
    $operationalKpis = [];
    if ($canCustomers) {
        $operationalKpis[] = ['label' => 'Customers', 'value' => $operationalStats['customers'] ?? 0, 'icon' => 'address-book', 'color' => 'indigo'];
    }
    if ($canInquiries) {
        $operationalKpis[] = ['label' => 'Inquiries', 'value' => $operationalStats['inquiries'] ?? 0, 'icon' => 'circle-question', 'color' => 'amber'];
    }
    if ($canQuotations) {
        $operationalKpis[] = ['label' => 'Quotations', 'value' => $operationalStats['quotations'] ?? 0, 'icon' => 'file-lines', 'color' => 'teal'];
    }
    if ($canBookings) {
        $operationalKpis[] = ['label' => 'Bookings', 'value' => $operationalStats['bookings'] ?? 0, 'icon' => 'calendar-check', 'color' => 'cyan'];
    }
    $masterDataKpis = [];
    if ($canVendors) {
        $masterDataKpis[] = ['label' => 'Vendors', 'value' => $masterDataStats['vendors'] ?? 0, 'icon' => 'handshake', 'route' => 'vendors.index'];
    }
    if ($canDestinations) {
        $masterDataKpis[] = ['label' => 'Destinations', 'value' => $masterDataStats['destinations'] ?? 0, 'icon' => 'map-location-dot', 'route' => 'destinations.index'];
    }
    if ($canActivities) {
        $masterDataKpis[] = ['label' => 'Activities', 'value' => $masterDataStats['activities'] ?? 0, 'icon' => 'person-hiking', 'route' => 'activities.index'];
    }
    if ($canAccommodations) {
        $masterDataKpis[] = ['label' => 'Accommodations', 'value' => $masterDataStats['accommodations'] ?? 0, 'icon' => 'hotel', 'route' => 'accommodations.index'];
    }
    if ($canTransports) {
        $masterDataKpis[] = ['label' => 'Transports', 'value' => $masterDataStats['transports'] ?? 0, 'icon' => 'bus', 'route' => 'transports.index'];
    }
    if ($canAttractions) {
        $masterDataKpis[] = ['label' => 'Attractions', 'value' => $masterDataStats['tourist_attractions'] ?? 0, 'icon' => 'landmark', 'route' => 'tourist-attractions.index'];
    }
    if ($canFoodBeverages) {
        $masterDataKpis[] = ['label' => 'F&B', 'value' => $masterDataStats['food_beverages'] ?? 0, 'icon' => 'utensils', 'route' => 'food-beverages.index'];
    }
    if ($canAirports) {
        $masterDataKpis[] = ['label' => 'Airports', 'value' => $masterDataStats['airports'] ?? 0, 'icon' => 'plane-departure', 'route' => 'airports.index'];
    }
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', 'Administrator Dashboard')
    @section('page_subtitle', 'Overview of system management, operations, and master data.')
    @section('page_actions')
        <div class="flex items-center gap-2">
            @if($canUsers)
                <a href="{{ route('users.create') }}"  class="btn-primary">
                    <i class="fa-solid fa-plus-circle mr-2"></i>New User
                </a>
            @endif
        </div>
    @endsection

    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
        <section class="xl:col-span-9 space-y-3">
            @if(!empty($systemKpis))
            <div class="sa-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">System Management</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Users, Roles, Modules</span>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($systemKpis as $card)
                        <a href="{{ isset($card['route']) && Route::has($card['route']) ? route($card['route']) : '#' }}"  class="sa-kpi sa-kpi-sm">
                            <div class="flex items-center justify-between">
                                <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                            </div>
                            <p>{{ $card['label'] }}</p>
                            <b>{{ number_format($card['value']) }}</b>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($operationalKpis))
            <div class="sa-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Operational Overview</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Sales & booking metrics</span>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($operationalKpis as $card)
                        <div class="sa-kpi sa-kpi-sm">
                            <div class="flex items-center justify-between">
                                <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                            </div>
                            <p>{{ $card['label'] }}</p>
                            <b>{{ number_format($card['value']) }}</b>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($masterDataKpis))
            <div class="sa-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Master Data Catalog</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Total records for each catalog</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($masterDataKpis as $card)
                        <a href="{{ isset($card['route']) && Route::has($card['route']) ? route($card['route']) : '#' }}"  class="block rounded-xl border border-slate-200 bg-slate-50 p-3 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-{{ $card['icon'] }} text-slate-500"></i>
                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $card['label'] }}</p>
                            </div>
                            <p class="mt-2 text-right text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($card['value']) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </section>

        <aside  class="xl:col-span-3 space-y-3">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Quick Actions</h3>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @if($canUsers)
                    <a href="{{ route('users.index') }}"  class="btn-secondary-sm text-center"><i class="fa-solid fa-user-gear mr-2"></i>Users</a>
                    @endif
                    @if($canRoles)
                    <a href="{{ route('roles.index') }}"  class="btn-secondary-sm text-center"><i class="fa-solid fa-user-shield mr-2"></i>Roles</a>
                    @endif
                    @if($canServices)
                    <a href="{{ route('services.index') }}"  class="btn-secondary-sm text-center"><i class="fa-solid fa-cubes mr-2"></i>Modules</a>
                    @endif
                    @if($canVendors)
                    <a href="{{ route('vendors.index') }}"  class="btn-secondary-sm text-center"><i class="fa-solid fa-handshake mr-2"></i>Vendors</a>
                    @endif
                </div>
            </div>
            
            @if($canQuotations)
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pending Quotations</h3>
                <div class="mt-3 space-y-2">
                    @forelse($pendingQuotations as $quotation)
                        <a href="{{ route('quotations.show', $quotation) }}"  class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                            <p class="font-bold text-slate-700 dark:text-slate-200">{{ $quotation->quotation_number }}</p>
                            <p class="text-slate-500 dark:text-slate-400">
                                Customer: {{ $quotation->inquiry?->customer?->name ?? 'N/A' }}
                            </p>
                        </a>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No pending quotations.</p>
                    @endforelse
                </div>
            </div>
            @endif

            @if($canUsers)
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recently Updated Users</h3>
                <div class="mt-3 space-y-2">
                    @forelse($recentUsers as $user)
                        <a href="{{ route('users.edit', $user) }}"  class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-slate-700 dark:text-slate-200">{{ $user->name }}</p>
                                <p class="text-slate-500 dark:text-slate-400">{{ $user->updated_at->diffForHumans() }}</p>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                        </a>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No users updated recently.</p>
                    @endforelse
                </div>
            </div>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const cards = document.querySelectorAll('.sa-card, .sa-kpi, .sa-mini');
        cards.forEach((el, idx) => {
            el.classList.add('sa-reveal');
            setTimeout(() => el.classList.add('is-in'), 35 * idx);
        });
    })();
</script>
@endpush

