@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--food-beverages">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    @if (isset($copiedFrom) && $copiedFrom)
                        <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-700/70 dark:bg-sky-900/20 dark:text-sky-200">
                            Data dicopy dari: <span class="font-semibold">{{ $copiedFrom->name }}</span>. Silakan cek ulang sebelum simpan.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('food-beverages.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('modules.food-beverages._form', ['buttonLabel' => 'Save F&B', 'prefill' => $prefill ?? []])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Isi Contract Rate dan Markup agar Publish Rate terhitung otomatis dengan standar nominal tanpa desimal.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection

