@extends('layouts.master')

@section('page_title', ui_phrase('Operations Dashboard'))
@section('page_subtitle', ui_phrase('Operational summary for reservation team.'))

@section('content')
    <div class="container py-4">
        <h2 class="mb-2">{{ ui_phrase('dashboard') }}</h2>
        <p class="text-muted">{{ ui_phrase('Summary for the Reservation role.') }}</p>
    </div>
@endsection

