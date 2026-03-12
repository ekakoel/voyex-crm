@extends('layouts.master')

@section('page_title', 'Module')
@section('page_subtitle', 'Page description')
@section('page_actions')
    <a href="{{ route('route.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
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
