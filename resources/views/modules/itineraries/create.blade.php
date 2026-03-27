@extends('layouts.master')



@section('content')

    <div class="space-y-6">

        

        <div class="grid gap-6 xl:grid-cols-12">

            <div class="xl:col-span-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form method="POST" action="{{ route('itineraries.store') }}">

                    @csrf

                    @include('modules.itineraries._form', ['buttonLabel' => 'Save Itinerary'])

                </form>

            </div>



            <aside  class="xl:col-span-4">

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Inquiry Detail</p>

                    <div id="inquiry-detail-empty" class="mt-2 text-xs text-gray-500 dark:text-gray-400">Pilih inquiry untuk melihat detail.</div>

                    <dl id="inquiry-detail-content" class="mt-2 hidden space-y-1.5 text-xs text-gray-700 dark:text-gray-200">

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Inquiry No</dt><dd id="inq-detail-number" class="font-medium text-right">-</dd></div>

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Customer</dt><dd id="inq-detail-customer" class="font-medium text-right">-</dd></div>

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Status</dt><dd id="inq-detail-status" class="font-medium text-right">-</dd></div>

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Priority</dt><dd id="inq-detail-priority" class="font-medium text-right">-</dd></div>

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Source</dt><dd id="inq-detail-source" class="font-medium text-right">-</dd></div>

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Assigned To</dt><dd id="inq-detail-assigned" class="font-medium text-right">-</dd></div>

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Deadline</dt><dd id="inq-detail-deadline" class="font-medium text-right">-</dd></div>

                        <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Created</dt><dd id="inq-detail-created" class="font-medium text-right">-</dd></div>

                        <div class="border-t border-gray-200 pt-1 dark:border-gray-700"><dt class="text-gray-500 dark:text-gray-400">Notes</dt><dd id="inq-detail-notes" class="mt-0.5 text-gray-700 dark:text-gray-200">-</dd></div>
                        <div class="border-t border-gray-200 pt-1 dark:border-gray-700"><dt class="text-gray-500 dark:text-gray-400">Reminder Note</dt><dd id="inq-detail-reminder-note" class="mt-0.5 text-gray-700 dark:text-gray-200">-</dd></div>
                        <div class="border-t border-gray-200 pt-1 dark:border-gray-700"><dt class="text-gray-500 dark:text-gray-400">Done Reason</dt><dd id="inq-detail-reminder-reason" class="mt-0.5 text-gray-700 dark:text-gray-200">-</dd></div>
                    </dl>

                </div>

                <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 itinerary-form-map-panel">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Itinerary Route Preview</p>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400">OpenStreetMap + OSRM</span>
                    </div>
                    <div id="itinerary-map" class="mt-2 w-full rounded-lg border border-gray-300 dark:border-gray-600" style="height: 360px;"></div>
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs dark:border-amber-700/40 dark:bg-amber-900/10">
                        <p class="font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-200">Route Debug</p>
                        <div id="itinerary-route-debug-summary" class="mt-2 text-amber-900 dark:text-amber-100">Preparing debug data...</div>
                        <pre id="itinerary-route-debug-details" class="mt-2 overflow-x-auto whitespace-pre-wrap text-[11px] leading-5 text-amber-900 dark:text-amber-100"></pre>
                    </div>
                </div>
</aside>
        </div>

    </div>

@endsection












