@extends('layouts.master')

@section('page_title', 'Airports')
@section('page_subtitle', 'Create a new airport record.')
@section('page_actions')
    <a href="{{ route('airports.index') }}" class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--airports">
        <div class="module-grid-9-3">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('airports.store') }}">
                        @csrf
                        @include('modules.airports._form', ['buttonLabel' => 'Save Airport'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Guidelines</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        <li>Isi kode dan nama airport secara konsisten.</li>
                        <li>Gunakan Google Maps URL lalu klik `Auto Fill` untuk akurasi lokasi.</li>
                        <li>Pastikan `Destination`, koordinat, dan status sudah sesuai sebelum simpan.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
@endsection


