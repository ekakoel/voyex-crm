@extends('layouts.master')

@section('page_title', ui_phrase('modules_currencies_edit_page_title'))
@section('page_subtitle', ui_phrase('modules_currencies_edit_page_subtitle'))

@section('content')
    <div class="space-y-6 module-page module-page--currencies">
        @section('page_actions')<a href="{{ route('currencies.index') }}"  class="btn-ghost">{{ ui_phrase('common_back') }}</a>@endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('currencies.update', $currency) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.currencies._form', ['currency' => $currency, 'buttonLabel' => ui_phrase('modules_currencies_update_currency')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-4">
                <div class="module-card p-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_currencies_rate_history') }}</p>
                    @if (!empty($rateHistories) && $rateHistories->count() > 0)
                        <div class="mt-3 space-y-3 text-xs text-gray-700 dark:text-gray-200">
                            @foreach ($rateHistories as $history)
                                <div class="rounded-lg mb-6 border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-semibold">{{ ui_phrase('modules_currencies_idr_rate') }}</span>
                                        <span class="text-gray-500 dark:text-gray-400"><x-local-time :value="$history->changed_at" /></span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_currencies_from') }}</span>
                                        <span>{{ $history->old_rate_to_idr !== null ? number_format((float) $history->old_rate_to_idr, 6, '.', ',') : '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_currencies_to') }}</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ number_format((float) $history->new_rate_to_idr, 6, '.', ',') }}</span>
                                    </div>
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('modules_currencies_updated_by') }}: {{ $history->changer?->name ?? '-' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_currencies_no_rate_changes') }}</p>
                    @endif
                </div>
            </aside>
        </div>
    </div>
@endsection






