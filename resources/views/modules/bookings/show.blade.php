@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Booking Detail</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Booking summary for {{ $booking->booking_number }}.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('bookings.edit', $booking) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Edit
                </a>
                <a href="{{ route('bookings.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    Back
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase text-gray-500">Booking Number</p>
                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->booking_number }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Status</p>
                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->status }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Travel Date</p>
                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->travel_date?->format('Y-m-d') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Quotation</p>
                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->quotation->quotation_number ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Customer</p>
                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->quotation->inquiry->customer->name ?? '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase text-gray-500">Notes</p>
                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->notes ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection


