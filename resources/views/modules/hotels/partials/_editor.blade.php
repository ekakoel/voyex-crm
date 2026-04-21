@php
    $steps = [
        'info' => 'Hotel Info',
        'rooms' => 'Rooms',
        'prices' => 'Prices',
    ];
    $today = now()->toDateString();
    $counts = [
        'rooms' => $hotel->rooms?->count() ?? 0,
        'prices' => $hotel->prices?->filter(fn ($price) => !empty($price->end_date) && (string) $price->end_date >= $today)->count() ?? 0,
    ];
    $step = array_key_exists($step, $steps) ? $step : 'info';
    $hasRooms = ($hotel->rooms?->count() ?? 0) > 0;
    $roomDependent = ['prices'];
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($step, $stepKeys, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $progress = (int) round((($currentIndex + 1) / max(count($stepKeys), 1)) * 100);

    $availableSteps = $stepKeys;
    if (! $hasRooms) {
        $availableSteps = array_values(array_diff($availableSteps, $roomDependent));
    }
    $availableIndex = array_search($step, $availableSteps, true);
    $availableIndex = $availableIndex === false ? 0 : $availableIndex;
    $prevStep = $availableIndex > 0 ? $availableSteps[$availableIndex - 1] : null;
    $nextStep = $availableIndex < count($availableSteps) - 1 ? $availableSteps[$availableIndex + 1] : null;

    $flashMessage = session('success') ?? session('warning');
    $flashType = session('success') ? 'success' : (session('warning') ? 'warning' : null);
@endphp

<div class="space-y-6 module-page--hotels" data-hotels-editor data-hotels-editor-step="{{ $step }}" data-hotel-id="{{ $hotel->getKey() }}" data-page-spinner="off">
    <div class="app-card p-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($steps as $key => $label)
                @php
                    $isActive = $step === $key;
                    $isLocked = in_array($key, $roomDependent, true) && ! $hasRooms;
                    $count = $counts[$key] ?? null;
                @endphp
                @if ($isLocked)
                    <span class="btn-muted-sm cursor-not-allowed inline-flex items-center gap-2">
                        <span>{{ $label }}</span>
                        @if (! is_null($count))
                            <span class="min-w-[1.25rem] h-5 px-1 rounded-full bg-slate-100 text-[11px] text-slate-700 inline-flex items-center justify-center">{{ $count }}</span>
                        @endif
                    </span>
                @else
                    <a
                        href="{{ route('hotels.edit', [$hotel, 'step' => $key]) }}"
                        class="{{ $isActive ? 'btn-primary-sm' : 'btn-outline-sm' }} inline-flex items-center gap-2"
                        data-hotel-step-link
                        data-hotel-step="{{ $key }}"
                    >
                        <span>{{ $label }}</span>
                        @if (! is_null($count))
                            <span class="min-w-[1.25rem] h-5 px-1 rounded-full bg-white/80 text-[11px] text-slate-700 inline-flex items-center justify-center">
                                {{ $count }}
                            </span>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
        @if (! $hasRooms)
            <p class="mt-3 text-xs text-gray-500">{{ __('Prices require at least one room.') }}</p>
        @endif
        <div class="mt-4">
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ __('Progress') }}</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-teal-500" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </div>

    <div data-hotels-flash-area>
        @if ($flashMessage && $flashType)
            <div class="rounded-lg border px-4 py-3 text-sm {{ $flashType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300' }}">
                {{ $flashMessage }}
            </div>
        @endif
    </div>

    <div data-hotels-step-panel>
        @if ($step === 'info')
            <form method="POST" action="{{ route('hotels.update-info', $hotel) }}" enctype="multipart/form-data" data-hotels-ajax-form data-hotels-step-form="info" data-disable-submit-lock="1" data-page-spinner="off">
                @csrf
                @method('PATCH')
                @include('modules.hotels.partials._info', ['hotel' => $hotel, 'buttonLabel' => 'Save & Continue', 'showActions' => false, 'destinations' => $destinations])
                @include('modules.hotels.partials._wizard-actions', compact('hotel', 'prevStep', 'nextStep'))
            </form>
        @elseif ($step === 'rooms')
            <form method="POST" action="{{ route('hotels.update-rooms', $hotel) }}" enctype="multipart/form-data" data-hotels-ajax-form data-hotels-step-form="rooms" data-disable-submit-lock="1" data-page-spinner="off">
                @csrf
                @method('PATCH')
                @include('modules.hotels.partials._rooms', ['hotel' => $hotel, 'roomViews' => $roomViews, 'buttonLabel' => 'Save Rooms'])
                @include('modules.hotels.partials._wizard-actions', compact('hotel', 'prevStep', 'nextStep'))
            </form>
        @elseif ($step === 'prices')
            <form method="POST" action="{{ route('hotels.update-prices', $hotel) }}" data-hotels-ajax-form data-hotels-step-form="prices" data-disable-submit-lock="1" data-page-spinner="off">
                @csrf
                @method('PATCH')
                @include('modules.hotels.partials._prices', ['hotel' => $hotel, 'roomOptions' => $roomOptions, 'buttonLabel' => 'Save Prices'])
                @include('modules.hotels.partials._wizard-actions', compact('hotel', 'prevStep', 'nextStep'))
            </form>
        @endif
    </div>
</div>





