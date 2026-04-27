@extends('layouts.master')

@section('content')
    @section('page_title', __('ui.sales_dashboard.page_title'))
    @section('page_subtitle', __('ui.sales_dashboard.page_subtitle'))
    <div class="container">

        <h2 class="mb-4">
            @if ($user->hasRole('Manager'))
                {{ __('ui.sales_dashboard.headings.sales_team_dashboard') }}
            @else
                {{ __('ui.sales_dashboard.headings.my_dashboard') }}
            @endif
        </h2>

        {{-- Main KPI row --}}
        @if($canBookings || $canInquiries)
            <div class="dashboard-kpi-grid grid grid-cols-2 gap-3 lg:grid-cols-3">
                @if($canBookings)
                    <div class="app-card app-kpi-card p-4">
                        <div class="flex items-center justify-between h-full relative">
                            <div class="data-card">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('ui.sales_dashboard.cards.monthly_revenue') }}</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900"><x-money :amount="$monthlyRevenue ?? 0" currency="IDR" /></p>
                            </div>
                            <div class="icon-kpi icon-kpi--emerald">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                @endif

                @if($canBookings && $canInquiries)
                    <div class="app-card app-kpi-card p-4">
                        <div class="flex items-center justify-between h-full relative">
                            <div class="data-card">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('ui.sales_dashboard.cards.conversion_rate') }}</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format((float) $conversionRate, 2) }}%</p>
                            </div>
                            <div class="icon-kpi icon-kpi--indigo">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                @endif

                @if($canInquiries)
                    <div class="app-card app-kpi-card p-4">
                        <div class="flex items-center justify-between h-full relative">
                            <div class="data-card">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('ui.sales_dashboard.cards.pending_followup') }}</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($pendingInquiries->count()) }}</p>
                            </div>
                            <div class="icon-kpi icon-kpi--amber">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <hr class="my-4">
        @endif

        {{-- List of inquiries that require follow-up --}}
        @if($canInquiries)
            <div class="row">
                <div class="col-12">
                    <h5>{{ __('ui.sales_dashboard.pending_inquiries.title') }}</h5>
                    <div class="card">
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                @forelse ($pendingInquiries as $inquiry)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $inquiry->inquiry_number }} - <strong>{{ $inquiry->customer->name }}</strong></span>
                                        <span class="badge bg-primary rounded-pill">{{ ui_term((string) $inquiry->status) }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item">{{ __('ui.sales_dashboard.pending_inquiries.empty') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
