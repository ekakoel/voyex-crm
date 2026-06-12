@php
    $info = $sidebarInfo ?? [];
    $statusCounts = $info['status_counts'] ?? [];
    $total = (int) ($info['total'] ?? 0);
    $travelToday = (int) ($info['travel_today'] ?? 0);
    $upcoming7Days = (int) ($info['upcoming_7_days'] ?? 0);
    $pendingPastTravelDate = (int) ($info['pending_past_travel_date'] ?? 0);
    $focus = $info['focus'] ?? ['title' => 'Operational Focus', 'items' => []];
    $focusTitle = (string) ($focus['title'] ?? 'Operational Focus');
    $focusItems = is_array($focus['items'] ?? null) ? $focus['items'] : [];
@endphp

<div class="app-card-stack">
    {{-- <section class="app-card p-5">
        <div>
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Booking Insights') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('Operational summary from current booking data.') }}</p>
        </div>

        <dl class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Total Bookings') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $total }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Travel Today') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $travelToday }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Upcoming 7 Days') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $upcoming7Days }}</dd>
            </div>
        </dl>
    </section> --}}

    <section class="app-card p-5">
        <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Need Attention') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Important booking statuses to review.') }}</p>
        </div>

        @if (!empty($focusItems))
            <div class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 dark:border-indigo-700 dark:bg-indigo-900/20">
                <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ ui_phrase($focusTitle) }}</p>
                <dl class="mt-2 space-y-1 text-xs text-indigo-700 dark:text-indigo-200">
                    @foreach ($focusItems as $item)
                        <div class="flex items-center justify-between gap-2">
                            <dt>{{ ui_phrase((string) ($item['label'] ?? 'Item')) }}</dt>
                            <dd class="font-semibold">{{ (int) ($item['value'] ?? 0) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        <dl class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Pending Confirmation') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($statusCounts['pending_confirmation'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Awaiting DP') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($statusCounts['awaiting_dp'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Confirmed') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($statusCounts['confirmed'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Ready to Operate') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($statusCounts['ready_to_operate'] ?? 0) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Closed') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($statusCounts['closed'] ?? 0) }}</dd>
            </div>
        </dl>

        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            {{ ui_phrase('Pending or draft with past travel date') }}: <span class="font-semibold">{{ $pendingPastTravelDate }}</span>
        </div>
    </section>
</div>
