@extends('layouts.master')

@section('page_title', 'Add Inquiry')
@section('page_subtitle', 'Create a new inquiry record.')
@section('page_actions')
    <a href="{{ route('inquiries.index') }}"  class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--inquiries">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('inquiries.store') }}">
                        @csrf
                        @include('modules.inquiries._form', [
                            'buttonLabel' => 'Save Inquiry',
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Lengkapi data inquiry, customer, dan prioritas agar proses lanjutan quotation lebih rapi.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection






