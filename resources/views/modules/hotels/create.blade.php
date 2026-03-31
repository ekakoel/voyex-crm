@extends('layouts.master')
@section('page_title', 'Hotels')
@section('page_subtitle', 'Create a new hotel record.')
@section('page_actions')
    <a href="{{ route('hotels.index') }}" class="btn-ghost">Back</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--hotels">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <form method="POST" action="{{ route('hotels.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('modules.hotels.partials._info', ['buttonLabel' => 'Save Hotel', 'destinations' => $destinations])
                </form>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Setelah Hotel Info tersimpan, kamu bisa lanjut ke tab Rooms dan Prices.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection





