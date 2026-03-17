@extends('layouts.master')

@section('content')
<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-4 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', 'Access Denied')
@section('page_subtitle', 'Anda tidak memiliki izin untuk mengakses halaman ini.')
    @section('page_actions')
        <a href="{{ route('dashboard') }}"  class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
            Back to Dashboard
        </a>
    @endsection

    <div class="sa-card p-6 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">403 — Akses Ditolak</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ $exception->getMessage() ?: 'Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.' }}
        </p>
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('dashboard') }}"  class="rounded-lg bg-slate-700 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-600">
                Kembali ke Dashboard
            </a>
            <a href="{{ url()->previous() }}" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                Kembali
            </a>
        </div>
    </div>
</div>
@endsection

