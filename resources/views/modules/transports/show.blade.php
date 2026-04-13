@extends('layouts.master')

@section('page_title', __('ui.modules.transports.show_page_title'))
@section('page_subtitle', __('ui.modules.transports.show_page_subtitle'))

@section('content')
    <div class="space-y-6 module-page module-page--transports">
        @section('page_actions')
            <a href="{{ route('transports.edit', $transport) }}" class="btn-primary">{{ __('ui.common.edit') }}</a>
            <a href="{{ route('transports.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
        @endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main app-card p-4">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.transports.transport_unit_detail') }}</h2>
                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.code') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->code ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.name') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->name }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.type') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->transport_type ? ucfirst(str_replace('_', ' ', (string) $transport->transport_type)) : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.vendor') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->vendor?->name ?? '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.transports.vehicle') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->brand_model ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.capacity') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ __('ui.modules.transports.seats_luggage', ['seats' => (int) ($transport->seat_capacity ?? 0), 'luggage' => $transport->luggage_capacity ?? '-']) }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.transports.contract_rate') }}:</span> <span class="text-gray-800 dark:text-gray-100">@if($transport->contract_rate !== null)<x-money :amount="(float) $transport->contract_rate" currency="IDR" />@else - @endif</span></div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.tourist_attractions.markup') }}:</span>
                        <span class="text-gray-800 dark:text-gray-100">
                            @if (($transport->markup_type ?? 'fixed') === 'percent')
                                {{ rtrim(rtrim(number_format((float) ($transport->markup ?? 0), 2, '.', ''), '0'), '.') }}%
                            @else
                                {{ \App\Support\Currency::format((float) ($transport->markup ?? 0), 'IDR') }}
                            @endif
                        </span>
                    </div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.tourist_attractions.publish') }} {{ __('ui.common.rate') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->publish_rate !== null ? \App\Support\Currency::format((float) $transport->publish_rate, 'IDR') : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.transports.overtime_rate') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->overtime_rate !== null ? \App\Support\Currency::format((float) $transport->overtime_rate, 'IDR') : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.transports.transmission') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->transmission ? ucfirst((string) $transport->transmission) : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.transports.ac_driver') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->air_conditioned ? 'AC' : __('ui.modules.transports.non_ac') }} | {{ $transport->with_driver ? __('ui.modules.transports.with_driver') : __('ui.modules.transports.without_driver') }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}:</span> <span class="{{ $transport->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $transport->is_active ? __('ui.common.active') : __('ui.common.inactive') }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.notes') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->notes ?: '-' }}</span></div>
                </div>
            </div>

            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $transport])
            </aside>
        </div>
    </div>
@endsection
