@extends('layouts.master')

@section('content')
    @section('page_title', ui_phrase('Sales Dashboard'))
    @section('page_subtitle', ui_phrase('Monitor sales team pipeline and follow-ups.'))
    <div class="container">

        <h2 class="mb-4">
            @if ($user->hasRole('Manager'))
                {{ ui_phrase('Sales Team Dashboard') }}
            @else
                {{ ui_phrase('My Dashboard') }}
            @endif
        </h2>

        {{-- Main KPI row --}}
        @if($canBookings || $canInquiries)
            <div class="dashboard-kpi-grid grid grid-cols-2 gap-3 lg:grid-cols-3">
                @if($canBookings)
                    <div class="app-card app-kpi-card p-4">
                        <div class="flex items-center justify-between h-full relative">
                            <div class="data-card">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ ui_phrase('Monthly Revenue') }}</p>
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
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ ui_phrase('Conversion Rate') }}</p>
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
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ ui_phrase('Pending Follow-up') }}</p>
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
                    <h5>{{ ui_phrase('Pending Inquiries') }}</h5>
                    <div class="card">
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                @forelse ($pendingInquiries as $inquiry)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $inquiry->inquiry_number }} - <strong>{{ $inquiry->customer->name }}</strong></span>
                                        <span class="badge bg-primary rounded-pill">{{ ui_phrase((string) $inquiry->status) }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item">{{ ui_phrase('No pending inquiries. Great job!') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection

