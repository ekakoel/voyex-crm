@extends('layouts.master')
@section('page_title', 'Vendors / Providers')
@section('page_subtitle', 'Create a new vendors / provider.')
@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('vendors.store') }}">
                        @csrf
                        @include('modules.vendors._form', ['buttonLabel' => 'Save Vendor'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Vendor akan terhubung ke activities, transports, dan food &amp; beverage.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection


