@extends('layouts.master')

@section('page_title', ui_phrase('modules_bookings_show_page_title'))
@section('page_subtitle', ui_phrase('modules_bookings_show_page_subtitle'))

@section('content')
    <div class="space-y-6 module-page module-page--bookings">
        @section('page_actions')
            @can('update', $booking)
                @if (! $booking->isFinal())
                    <a href="{{ route('bookings.edit', $booking) }}"  class="btn-primary">
                        {{ ui_phrase('common_edit') }}
                    </a>
                @endif
            @endcan
            <a href="{{ route('bookings.index') }}"  class="btn-ghost">{{ ui_phrase('common_back') }}</a>
        @endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-card p-6 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('common_booking_number') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->booking_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('common_status') }}</p>
                            <div class="mt-1">
                                <x-status-badge :status="$booking->status" size="xs" />
                            </div>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('modules_bookings_travel_date') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->travel_date?->format('Y-m-d') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('common_quotation') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->quotation->quotation_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('common_customer') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('common_notes') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->notes ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $booking])
            </aside>
        </div>
    </div>
@endsection





