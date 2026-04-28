@extends('layouts.master')

@section('page_title', ui_phrase('modules_customers_import_preview_page_title'))
@section('page_subtitle', ui_phrase('modules_customers_import_preview_page_subtitle'))
@section('page_actions')
    <a href="{{ route('customers.import') }}"  class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--customers">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                <div class="md:hidden space-y-3">
                    @forelse ($rows as $row)
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $row['data']['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['data']['email'] ?? '-' }}</p>
                                </div>
                                @if ($row['action'] === 'create')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ ui_phrase('common_create') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ ui_phrase('modules_customers_import_skip') }}</span>
                                @endif
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('common_code') }}</div><div>{{ $row['data']['code'] ?? '-' }}</div>
                                <div>{{ ui_phrase('modules_customers_phone') }}</div><div>{{ $row['data']['phone'] ?? '-' }}</div>
                                <div>{{ ui_phrase('modules_customers_country') }}</div><div>{{ $row['data']['country'] ?? '-' }}</div>
                                <div>{{ ui_phrase('modules_customers_company') }}</div><div>{{ $row['data']['company_name'] ?? '-' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            {{ ui_phrase('modules_customers_import_no_preview_data') }}
                        </div>
                    @endforelse
                </div>

                <div class="hidden md:block overflow-x-auto app-card">
            <table class="app-table w-full min-w-[720px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_code') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_customers_email') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_customers_phone') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_customers_type') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_customers_country') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['data']['name'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['data']['code'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['data']['email'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['data']['phone'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ ui_term((string) ($row['data']['customer_type'] ?? '-')) }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['data']['country'] ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($row['action'] === 'create')
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ ui_phrase('common_create') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ ui_phrase('modules_customers_import_skip') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_customers_import_no_preview_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('customers.import.store') }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <button  class="btn-primary">{{ ui_phrase('modules_customers_import_now') }}</button>
                    <a href="{{ route('customers.index') }}"  class="btn-secondary">{{ ui_phrase('common_cancel') }}</a>
                </form>
            </div>
            <aside  class="space-y-6 xl:col-span-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        {{ ui_phrase('modules_customers_import_mode') }}: <span class="font-semibold">{{ $mode === 'update' ? ui_phrase('modules_customers_import_mode_update_duplicates') : ui_phrase('modules_customers_import_mode_skip_duplicates') }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection

