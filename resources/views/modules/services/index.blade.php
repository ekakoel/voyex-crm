@extends('layouts.master')
@section('page_title', 'Services')
@section('page_subtitle', 'Manage module availability.')
@section('content')
    <div class="space-y-6 module-page module-page--services">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="space-y-4">
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        <div class="app-card-grid app-card-grid--services">
            @forelse ($modules as $module)
                <div class="app-card p-5 grid content-between">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $module->name }}</h2>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $module->is_enabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' }}">
                            {{ $module->is_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {{ $module->description ?: '-' }}
                    </p>
                    <form method="POST" action="{{ route('services.toggle', $module) }}" class="mt-4 text-right">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-secondary">
                            {{ $module->is_enabled ? 'Disable Module' : 'Enable Module' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="app-card p-6 text-sm text-gray-600 dark:text-gray-300">
                    No module data available yet. Run migrations and seeders first.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
