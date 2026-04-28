@extends('layouts.master')

@section('page_title', ui_phrase('operations_dashboard_page_title'))
@section('page_subtitle', ui_phrase('operations_dashboard_page_subtitle'))

@section('content')
    <div class="container py-4">
        <h2 class="mb-2">{{ ui_term('dashboard') }}</h2>
        <p class="text-muted">{{ ui_phrase('operations_dashboard_summary') }}</p>
    </div>
@endsection

