@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--transports">
        @section('page_actions')
            <a href="{{ route('transports.edit', $transport) }}" class="btn-primary">Edit</a>
            <a href="{{ route('transports.index') }}" class="btn-ghost">Back</a>
        @endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main app-card p-4">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Transport Unit Detail</h2>
                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                    <div><span class="text-gray-500 dark:text-gray-400">Code:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->code ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Name:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->name }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Type:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->transport_type ? ucfirst(str_replace('_', ' ', (string) $transport->transport_type)) : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Vendor:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->vendor?->name ?? '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Vehicle:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->brand_model ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Capacity:</span> <span class="text-gray-800 dark:text-gray-100">{{ (int) ($transport->seat_capacity ?? 0) }} seats, luggage {{ $transport->luggage_capacity ?? '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Contract Rate:</span> <span class="text-gray-800 dark:text-gray-100">@if($transport->contract_rate !== null)<x-money :amount="(float) $transport->contract_rate" currency="IDR" />@else - @endif</span></div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Markup:</span>
                        <span class="text-gray-800 dark:text-gray-100">
                            @if (($transport->markup_type ?? 'fixed') === 'percent')
                                {{ rtrim(rtrim(number_format((float) ($transport->markup ?? 0), 2, '.', ''), '0'), '.') }}%
                            @else
                                {{ \App\Support\Currency::format((float) ($transport->markup ?? 0), 'IDR') }}
                            @endif
                        </span>
                    </div>
                    <div><span class="text-gray-500 dark:text-gray-400">Publish Rate:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->publish_rate !== null ? \App\Support\Currency::format((float) $transport->publish_rate, 'IDR') : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Overtime Rate:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->overtime_rate !== null ? \App\Support\Currency::format((float) $transport->overtime_rate, 'IDR') : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Transmission:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->transmission ? ucfirst((string) $transport->transmission) : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">AC/Driver:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->air_conditioned ? 'AC' : 'Non-AC' }} | {{ $transport->with_driver ? 'With Driver' : 'Without Driver' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Status:</span> <span class="{{ $transport->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $transport->is_active ? 'Active' : 'Inactive' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Notes:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->notes ?: '-' }}</span></div>
                </div>
            </div>

            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $transport])
            </aside>
        </div>
    </div>
@endsection
