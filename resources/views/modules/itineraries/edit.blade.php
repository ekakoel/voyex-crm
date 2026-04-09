@extends('layouts.master')



@section('content')
    <div class="space-y-6 module-page module-page--itineraries">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('itineraries.update', $itinerary) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.itineraries._form', [
                            'itinerary' => $itinerary,
                            'buttonLabel' => 'Update Itinerary',
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-4">

                <div id="inquiry-detail-card"
                    class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Inquiry Detail
                    </p>

                    <div id="inquiry-detail-empty" class="mt-2 text-xs text-gray-500 dark:text-gray-400">Pilih inquiry untuk
                        melihat detail.</div>

                    <dl id="inquiry-detail-content"
                        class="mt-2 hidden space-y-1.5 text-xs text-gray-700 dark:text-gray-200">

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Inquiry No</dt>
                            <dd id="inq-detail-number" class="font-medium text-right">-</dd>
                        </div>

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Customer</dt>
                            <dd id="inq-detail-customer" class="font-medium text-right">-</dd>
                        </div>

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                            <dd id="inq-detail-status" class="font-medium text-right">-</dd>
                        </div>

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Priority</dt>
                            <dd id="inq-detail-priority" class="font-medium text-right">-</dd>
                        </div>

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Source</dt>
                            <dd id="inq-detail-source" class="font-medium text-right">-</dd>
                        </div>

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Assigned To</dt>
                            <dd id="inq-detail-assigned" class="font-medium text-right">-</dd>
                        </div>

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Deadline</dt>
                            <dd id="inq-detail-deadline" class="font-medium text-right">-</dd>
                        </div>

                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                            <dd id="inq-detail-created" class="font-medium text-right">-</dd>
                        </div>

                        <div class="border-t border-gray-200 pt-1 dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400">Notes</dt>
                            <dd id="inq-detail-notes" class="mt-0.5 text-gray-700 dark:text-gray-200">-</dd>
                        </div>
                        <div class="border-t border-gray-200 pt-1 dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400">Reminder Note</dt>
                            <dd id="inq-detail-reminder-note" class="mt-0.5 text-gray-700 dark:text-gray-200">-</dd>
                        </div>
                        <div class="border-t border-gray-200 pt-1 dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400">Done Reason</dt>
                            <dd id="inq-detail-reminder-reason" class="mt-0.5 text-gray-700 dark:text-gray-200">-</dd>
                        </div>
                    </dl>

                </div>
                <div class="app-card p-4 space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Activity Timeline</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">Detailed create/update audit log for this itinerary.</p>
                    </div>
                    <x-activity-timeline :activities="$activityLogs" />
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:sticky xl:top-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Itinerary
                        Route Preview</p>
                    <div id="itinerary-map-day-tabs" class="mt-2 flex flex-wrap items-center gap-2">
                        <button type="button" data-map-day=""
                            class="itinerary-map-day-tab inline-flex items-center rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200"
                            aria-pressed="true">All Days</button>
                    </div>
                    <div id="itinerary-map-legend"
                        class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-gray-600 dark:text-gray-300"></div>
                    <div id="itinerary-map" class="mt-2 w-full rounded-lg border border-gray-300 dark:border-gray-600"
                        style="height: 360px;"></div>
                </div>

            </aside>
        </div>
    </div>
@endsection
