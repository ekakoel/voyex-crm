@extends('layouts.master')

@section('page_title', __('ui.modules.tourist_attractions.edit_page_title'))
@section('page_subtitle', __('ui.modules.tourist_attractions.edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('tourist-attractions.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--tourist-attractions">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('tourist-attractions.update', $touristAttraction) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.tourist-attractions._form', ['touristAttraction' => $touristAttraction, 'buttonLabel' => __('ui.modules.tourist_attractions.update_attraction')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $touristAttraction])
            </aside>
        </div>
    </div>
@endsection



