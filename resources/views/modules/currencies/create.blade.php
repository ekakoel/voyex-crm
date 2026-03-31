@extends('layouts.master')

@section('page_title', 'Create Currency')
@section('page_subtitle', 'Add a new currency and conversion baseline.')
@section('page_actions')
    <a href="{{ route('currencies.index') }}" class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--currencies">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('currencies.store') }}">
                        @csrf
                        @include('modules.currencies._form', ['buttonLabel' => 'Create Currency'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Isi nama, code, symbol, dan rate ke IDR. Pastikan rate valid sebelum menyimpan.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
