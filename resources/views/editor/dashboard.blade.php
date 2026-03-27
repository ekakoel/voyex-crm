@extends('layouts.master')

@section('content')
@php
    $kpiCards = [];
    if ($canDestinations) {
        $kpiCards[] = ['label' => 'Destinations', 'value' => $catalogCounts['destinations'] ?? 0, 'icon' => 'map-location-dot', 'color' => 'sky'];
    }
    if ($canVendors) {
        $kpiCards[] = ['label' => 'Vendors', 'value' => $catalogCounts['vendors'] ?? 0, 'icon' => 'handshake', 'color' => 'teal'];
    }
    if ($canActivities) {
        $kpiCards[] = ['label' => 'Activities', 'value' => $catalogCounts['activities'] ?? 0, 'icon' => 'person-hiking', 'color' => 'lime'];
    }
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', 'Editor Dashboard')
    @section('page_subtitle', 'Kelola katalog layanan dan konten master.')
    @section('page_actions')
        <span class="text-xs text-slate-500 dark:text-slate-400">Updated: {{ now()->format('d M Y H:i') }}</span>
    @endsection

    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
        <section class="xl:col-span-8 space-y-3">
            <div class="sa-card p-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($kpiCards as $card)
                        <div class="sa-kpi">
                            <div class="flex items-center justify-between">
                                <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                                <span class="text-[10px] text-slate-400">live</span>
                            </div>
                            <p>{{ $card['label'] }}</p>
                            <b>{{ number_format($card['value']) }}</b>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($canDestinations)
                <div class="sa-card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Latest Updates</h2>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Content changes</span>
                    </div>
                <div class="mt-4 grid grid-cols-1 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">Destinations</p>
                        <div class="mt-2 space-y-1 text-xs">
                            @forelse($recentDestinations as $item)
                                <p class="text-slate-500 dark:text-slate-400">{{ $item->name }} • {{ optional($item->updated_at)->diffForHumans() }}</p>
                            @empty
                                <p class="text-slate-500 dark:text-slate-400">No updates.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </section>

        <aside  class="xl:col-span-4 space-y-3">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Catalog Summary</h3>
                <div class="mt-3 space-y-2 text-xs">
                    @if($canDestinations)
                        <div class="sa-mini"><span>Destinations</span><b>{{ number_format($catalogCounts['destinations'] ?? 0) }}</b></div>
                    @endif
                    @if($canVendors)
                        <div class="sa-mini"><span>Vendors</span><b>{{ number_format($catalogCounts['vendors'] ?? 0) }}</b></div>
                    @endif
                    @if($canActivities)
                        <div class="sa-mini"><span>Activities</span><b>{{ number_format($catalogCounts['activities'] ?? 0) }}</b></div>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const cards = document.querySelectorAll('.sa-card, .sa-kpi, .sa-mini');
        cards.forEach((el, idx) => {
            el.classList.add('sa-reveal');
            setTimeout(() => el.classList.add('is-in'), 35 * idx);
        });
    })();
</script>
@endpush
