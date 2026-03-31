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
        <div class="module-grid-8-4">
            <section class="module-grid-main module-card p-6">
                <!-- Main content -->
            </section>
            <aside class="module-grid-side module-card p-6">
                <!-- Supporting panel -->
            </aside>
        </div>
    </div>
@endsection

