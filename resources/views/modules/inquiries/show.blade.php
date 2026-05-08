@extends('layouts.master')



@section('page_title', ui_phrase('Inquiry Detail'))

@section('page_subtitle', ui_phrase('Review complete inquiry information.'))

@section('page_actions')

    @can('update', $inquiry)

        @if (!in_array(($inquiry->quotation->status ?? ''), ['approved', \App\Models\Quotation::FINAL_STATUS], true) && ! $inquiry->isFinal())

            <a href="{{ route('inquiries.edit', $inquiry) }}"  class="btn-secondary">

                {{ ui_phrase('Edit Inquiry') }}
            </a>
        @endif

    @endcan

@endsection



@section('content')

    <div class="module-page module-page--inquiries">

        @if (session('success'))

            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">

                {{ session('success') }}

            </div>

        @endif

        @if (session('error'))

            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">

                {{ session('error') }}

            </div>

        @endif



        @if ($errors->any())

            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">

                {{ $errors->first() }}

            </div>

        @endif



        <div class="module-grid-8-4">

            <div class="module-grid-side order-2">
                <div class="app-card p-5">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('related records') }}</h2>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('related itineraries') }}</h3>
                            <div class="mt-2 space-y-2">
                                @forelse (($itineraries ?? collect()) as $itinerary)
                                    <a href="{{ route('itineraries.show', $itinerary) }}" class="block rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900/30">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $itinerary->title ?: '-' }}</p>
                                            <x-status-badge :status="$itinerary->status" size="xs" />
                                        </div>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('no related itineraries') }}</p>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('related quotations') }}</h3>
                            <div class="mt-2 space-y-2">
                                @forelse (($quotations ?? collect()) as $quotation)
                                    <a href="{{ route('quotations.show', $quotation) }}" class="block rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900/30">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->order_number ?: ($quotation->quotation_number ?: '-') }}</p>
                                            <x-status-badge :status="$quotation->status" size="xs" />
                                        </div>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('no related quotations') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Activity Timeline') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('tracking changes') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
            </div>



            <div class="module-grid-main order-1">
                <div class="app-card p-5">

                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Inquiry Overview') }}</h2>

                    <dl class="app-dl mt-4 text-sm">

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer:') }}</dt>

                        <dd class="text-right font-medium text-gray-800 dark:text-gray-100">({{ $inquiry->customer->code ?? '-' }}) {{ $inquiry->customer->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}</dt>

                        <dd><x-status-badge :status="$inquiry->status" size="xs" /></dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Priority') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ ui_phrase((string) $inquiry->priority) }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Source') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->source ? ui_phrase((string) $inquiry->source) : '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Assigned to:') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->assignedUser->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Deadline') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->deadline?->format('Y-m-d') ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Reminder Email') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->reminder_enabled ? ui_phrase('Enabled') : ui_phrase('Disabled') }}</dd>

                    </div>

                    <div>

                        <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Notes') }}:</div>

                        <dd class="text-left font-medium text-gray-800 dark:text-gray-100">{!! $inquiry->notes ?: '-' !!}</dd>

                    </div>

                    </dl>

                </div>

            </div>

        </div>

    </div>

@endsection









