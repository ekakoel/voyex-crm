@extends('layouts.master')

@section('page_title', 'Airports')
@section('page_subtitle', 'Edit airport record.')
@section('page_actions')
    <a href="{{ route('airports.show', $airport) }}" class="btn-outline">View</a>
    <a href="{{ route('airports.index') }}" class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--airports">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('airports.update', $airport) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.airports._form', ['airport' => $airport, 'buttonLabel' => 'Update Airport'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Guidelines</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        <li>Perubahan lokasi akan berpengaruh ke itinerary dan map.</li>
                        <li>Gunakan `Auto Fill` jika memperbarui link Google Maps.</li>
                        <li>Cek status airport sebelum menyimpan perubahan.</li>
                    </ul>
                </div>
                @include('partials._audit-info', ['record' => $airport])
            </aside>
        </div>
    </div>
@endsection


