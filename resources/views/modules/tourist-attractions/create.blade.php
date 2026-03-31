@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--tourist-attractions">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('tourist-attractions.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('modules.tourist-attractions._form', ['buttonLabel' => 'Save Attraction'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Isi durasi kunjungan ideal dan komponen biaya untuk kalkulasi itinerary.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection




