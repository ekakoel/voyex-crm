@extends('layouts.master')
@section('page_title', __('ui.modules.bookings.page_title'))
@section('page_subtitle', __('ui.modules.bookings.page_subtitle'))
@section('page_actions')
    <a href="{{ route('bookings.export', request()->query()) }}" class="btn-secondary">{{ __('Export CSV') }}</a>
    <a href="{{ route('bookings.create') }}" class="btn-primary">{{ __('ui.modules.bookings.add_booking') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--bookings" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('bookings.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('ui.modules.bookings.search') }}" class="app-input sm:col-span-2" data-service-filter-input>
            <select name="status" class="app-input" data-service-filter-input>
                <option value="">{{ __('ui.common.status') }}</option>
                @foreach (\App\Models\Booking::STATUS_OPTIONS as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="quotation_id" class="app-input" data-service-filter-input>
                <option value="">{{ __('ui.modules.bookings.quotation') }}</option>
                @foreach ($quotations as $quotation)
                    <option value="{{ $quotation->id }}" @selected((string) request('quotation_id') === (string) $quotation->id)>
                        {{ $quotation->quotation_number }} - {{ $quotation->inquiry?->customer?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
            <input name="travel_from" type="date" value="{{ request('travel_from') }}" class="app-input" data-service-filter-input>
            <input name="travel_to" type="date" value="{{ request('travel_to') }}" class="app-input" data-service-filter-input>
            <select name="per_page" class="app-input" data-service-filter-input>
                @foreach ([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.index.per_page_option', ['size' => $size]) }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                <a href="{{ route('bookings.index') }}" class="btn-ghost" data-service-filter-reset>{{ __('ui.common.reset') }}</a>
            </div>
        </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-service-filter-results>
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
        <div class="md:hidden space-y-3">
            @forelse ($bookings as $booking)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $booking->booking_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $booking->quotation?->quotation_number ?? '-' }} - {{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}
                            </p>
                        </div>
                        <x-status-badge :status="$booking->status" size="xs" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ __('ui.modules.bookings.travel_date') }}</div>
                        <div>{{ $booking->travel_date?->format('Y-m-d') ?? '-' }}</div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('bookings.show', $booking) }}"  class="btn-outline-sm" title="{{ __('ui.common.detail') }}" aria-label="{{ __('ui.common.detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ __('ui.common.detail') }}</span></a>
                        @can('update', $booking)
                            @if (! $booking->isFinal())
                            <a href="{{ route('bookings.edit', $booking) }}"  class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                            @endif
                        @endcan
                        @can('delete', $booking)
                            @if (! $booking->isFinal())
                                <form action="{{ route('bookings.destroy', $booking) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('{{ __('ui.modules.bookings.confirm_delete') }}')"   class="btn-danger-sm">
                                        {{ __('ui.common.delete') }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('ui.index.no_data_available', ['entity' => __('ui.entities.bookings')]) }}
                </div>
            @endforelse
        </div>
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.bookings.booking_no') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.bookings.quotation') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.bookings.travel_date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ __('ui.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($bookings as $index => $booking)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $booking->booking_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ $booking->quotation?->quotation_number ?? '-' }} - {{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $booking->travel_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <x-status-badge :status="$booking->status" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('bookings.show', $booking) }}"  class="btn-outline-sm" title="{{ __('ui.common.detail') }}" aria-label="{{ __('ui.common.detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ __('ui.common.detail') }}</span></a>
                                @can('update', $booking)
                                    @if (! $booking->isFinal())
                                        <a href="{{ route('bookings.edit', $booking) }}"  class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                                    @endif
                                @endcan
                                @can('delete', $booking)
                                    @if (! $booking->isFinal())
                                        <form action="{{ route('bookings.destroy', $booking) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('{{ __('ui.modules.bookings.confirm_delete') }}')"   class="btn-danger-sm">{{ __('ui.common.delete') }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.bookings')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div>{{ $bookings->links() }}</div>
            </div>
        </div>
</div>
@endsection



