@extends('layouts.master')

@section('page_title', 'Module')
@section('page_subtitle', 'Page description')
@section('page_actions')
    <a href="{{ route('route.create') }}"  class="btn-primary">
        Action
    </a>
@endsection

@section('content')
    <div class="space-y-6 module-page">
        <div class="module-card p-6">
            <!-- Page content -->
        </div>
    </div>
@endsection

