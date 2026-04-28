@extends('layouts.master')
@section('page_title', ui_phrase('modules_users_page_title'))
@section('page_subtitle', ui_phrase('modules_users_page_subtitle'))
@section('page_actions')
    <a href="{{ route('users.create') }}" class="btn-primary">{{ ui_phrase('modules_users_add_employee') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--users" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="search" value="{{ request('search') }}" placeholder="{{ ui_phrase('modules_users_search_name_email') }}" class="app-input sm:col-span-2" data-service-filter-input>
                        <select name="role" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('modules_users_all_roles') }}</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase('index_per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('users.index') }}" class="btn-ghost" data-service-filter-reset>{{ ui_phrase('common_reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="xl:col-span-9" data-service-filter-results>
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif
        <div class="md:hidden space-y-3">
            @forelse ($users as $user)
                <div class="app-card p-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @forelse ($user->roles as $role)
                            <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $role->name }}</span>
                        @empty
                            <span class="text-xs text-gray-500 dark:text-gray-400">-</span>
                        @endforelse
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('users.edit', $user) }}"  class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                        @can('module.user_manager.delete')
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('{{ ui_phrase('modules_users_confirm_delete_employee') }}')"   class="btn-danger-sm">
                                    {{ ui_phrase('common_delete') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ ui_phrase('modules_users_no_employee_data') }}
                </div>
            @endforelse
        </div>
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Email') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Role') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('common_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($users as $index => $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @forelse ($user->roles as $role)
                                    <span class="mr-1 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    -
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('users.edit', $user) }}"
                                    class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                                @can('module.user_manager.delete')
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            onclick="return confirm('{{ ui_phrase('modules_users_confirm_delete_employee') }}')"
                                              class="btn-danger-sm">{{ ui_phrase('common_delete') }}
                                        </button>
                                    </form>
                                @endcan
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('modules_users_no_employee_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div>
            {{ $users->links() }}
        </div>
            </div>
        </div>
</div>
@endsection


