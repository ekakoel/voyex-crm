@extends('layouts.master')

@section('page_title', 'Create Quotation')
@section('page_subtitle', 'Generate quotation from itinerary items and pricing rules.')
@section('page_actions')
    <a href="{{ route('quotations.index') }}" class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        <div class="module-grid-9-3">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('quotations.store') }}">
                        @csrf
                        @include('modules.quotations._form', [
                            'buttonLabel' => 'Save Quotation',
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Pastikan itinerary, item biaya, dan validitas quotation terisi sebelum disimpan.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection





