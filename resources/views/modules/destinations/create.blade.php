@extends('layouts.master')
@section('page_title', 'Destinations')
@section('page_subtitle', 'Create a new destinations.')
@section('content')
    <div class="space-y-6 module-page module-page--destinations">
        <div class="module-grid-9-3">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('destinations.store') }}">
                        @csrf
                        @include('modules.destinations._form', ['buttonLabel' => 'Save Destination'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Isi data lokasi utama karena destination menjadi referensi untuk module lain.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection


