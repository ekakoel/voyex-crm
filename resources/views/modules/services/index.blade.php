@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Module Management</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-300">
                Enable or disable modules based on business needs.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @forelse ($modules as $module)
                <div class="grid bg-white content-between dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
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

                        <button type="submit" class="w-auto rounded-lg px-3 py-2 text-sm font-medium transition {{ $module->is_enabled ? 'bg-gray-600 text-white hover:bg-gray-400' : 'bg-gray-600 text-white hover:bg-gray-400' }}">
                            <i class="fa-solid fa-ban text-white mr-50"></i>
                            {{ $module->is_enabled ? 'Disable Module' : 'Enable Module' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="col-span-full rounded-lg bg-white dark:bg-gray-800 p-6 shadow text-sm text-gray-600 dark:text-gray-300">
                    No module data available yet. Run migrations and seeders first.
                </div>
            @endforelse
        </div>
    </div>
@endsection


