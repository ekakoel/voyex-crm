@extends('layouts.master')
@section('page_title', ui_phrase('Employees'))
@section('page_subtitle', ui_phrase('Manage internal user accounts and role assignments.'))
@section('page_actions')
    <a href="{{ route('users.create') }}" class="btn-primary">{{ ui_phrase('Add Employee') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--users" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
                <div class="app-card p-5">
                    <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="search" value="{{ request('search') }}" placeholder="{{ ui_phrase('Search') }}" class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                        <select name="role" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('All roles') }}</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('users.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
        <div class="md:hidden space-y-3">
            @forelse ($users as $user)
                <div class="app-card relative p-4 pt-5">
                    <div class="absolute right-3 top-3 z-10">
                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                            <a href="{{ route('users.edit', $user) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Edit') }}</span>
                            </a>
                            @can('module.user_manager.delete')
                                <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                <x-ui.confirm-action
                                    :action="route('users.destroy', $user)"
                                    method="DELETE"
                                    :modal-name="'users-index-delete-mobile-' . $user->id"
                                    :title="ui_phrase('Delete') . ' ' . ui_phrase('Employee')"
                                    :message="ui_phrase('confirm delete employee')"
                                    :impact-title="__('confirm.important_warning')"
                                    :impact-items="[
                                        __('confirm.delete_itinerary_info_1'),
                                        __('confirm.delete_itinerary_info_2'),
                                    ]"
                                    :notice-message="__('confirm.notification_after_action')"
                                    notice-tone="danger"
                                    :confirm-label="ui_phrase('Delete')"
                                    :trigger-label="ui_phrase('Delete')"
                                    trigger-icon="fa-solid fa-trash w-4"
                                    trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                    confirm-class="btn-danger-sm"
                                />
                            @endcan
                        </x-ui.table-action-dropdown>
                    </div>
                    <div class="pr-12">
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
                </div>
            @empty
                <x-module-empty-state :title="ui_phrase('no employee data')" :message="ui_phrase('Try changing filter criteria or add a new employee.')" />
            @endforelse
        </div>
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Email') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Role') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($users as $index => $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ($users->firstItem() ?? 1) + $index }}</td>
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
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ route('users.edit', $user) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    @can('module.user_manager.delete')
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action
                                            :action="route('users.destroy', $user)"
                                            method="DELETE"
                                            :modal-name="'users-index-delete-desktop-' . $user->id"
                                            :title="ui_phrase('Delete') . ' ' . ui_phrase('Employee')"
                                            :message="ui_phrase('confirm delete employee')"
                                            :impact-title="__('confirm.important_warning')"
                                            :impact-items="[
                                                __('confirm.delete_itinerary_info_1'),
                                                __('confirm.delete_itinerary_info_2'),
                                            ]"
                                            :notice-message="__('confirm.notification_after_action')"
                                            notice-tone="danger"
                                            :confirm-label="ui_phrase('Delete')"
                                            :trigger-label="ui_phrase('Delete')"
                                            trigger-icon="fa-solid fa-trash w-4"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                            confirm-class="btn-danger-sm"
                                        />
                                    @endcan
                                </x-ui.table-action-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6">
                                <x-module-empty-state :title="ui_phrase('no employee data')" :message="ui_phrase('Try changing filter criteria or add a new employee.')" />
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
@endsection






