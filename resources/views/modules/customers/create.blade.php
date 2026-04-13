@extends('layouts.master')

@section('page_title', __('ui.modules.customers.create_page_title'))
@section('page_subtitle', __('ui.modules.customers.create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('customers.index') }}"  class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--customers">
        <div class="module-grid-8-4">
            <div class="module-grid-main space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <form method="POST" action="{{ route('customers.store') }}">
                        @csrf
                        @include('modules.customers._form', [
                            'buttonLabel' => __('ui.modules.customers.save_customer'),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                <div class="rounded-xl border border-slate-200/80 bg-white p-5 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.common.info') }}</p>
                    <p class="mt-2">{{ __('ui.modules.customers.info_text') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection

