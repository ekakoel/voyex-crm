@extends('layouts.master')

@section('page_title', __('ui.operations_dashboard.page_title'))
@section('page_subtitle', __('ui.operations_dashboard.page_subtitle'))

@section('content')
    <div class="container py-4">
        <h2 class="mb-2">{{ ui_term('dashboard') }}</h2>
        <p class="text-muted">{{ __('ui.operations_dashboard.summary') }}</p>
    </div>
@endsection
