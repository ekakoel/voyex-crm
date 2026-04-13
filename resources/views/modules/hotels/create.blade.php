@extends('layouts.master')
@section('page_title', __('ui.modules.hotels.add_hotel'))
@section('page_subtitle', __('ui.modules.hotels.create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('hotels.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--hotels">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <form method="POST" action="{{ route('hotels.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('modules.hotels.partials._info', ['buttonLabel' => __('ui.modules.hotels.save_hotel'), 'destinations' => $destinations])
                </form>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.common.info') }}</p>
                    <p class="mt-2">{{ __('ui.modules.hotels.info_text') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection



