<div data-roles-index-results>
    <div class="space-y-4">

        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Role') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Permissions') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($roles as $index => $role)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ($roles->firstItem() ?? 1) + $index }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $role->name }}</span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:bg-slate-700/40 dark:text-slate-200">
                                            {{ $role->permissions->count() }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    @forelse ($role->permissions->take(5) as $permission)
                                        <span class="mr-1 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            {{ $permission->name }}
                                        </span>
                                    @empty
                                        -
                                    @endforelse
                                    @if ($role->permissions->count() > 5)
                                        <span class="mr-1 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700/40 dark:text-gray-300">
                                            {{ ui_phrase('more', ['count' => $role->permissions->count() - 5]) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                        <a href="{{ route('roles.edit', $role) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Edit') }}</span>
                                        </a>
                                        <a href="{{ route('roles.create', ['template_role_id' => $role->id]) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-copy w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Clone') }}</span>
                                        </a>
                                        @can('module.role_manager.delete')
                                            <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                            <x-ui.confirm-action
                                                :action="route('roles.destroy', $role)"
                                                method="DELETE"
                                                :modal-name="'roles-index-delete-desktop-' . $role->id"
                                                :title="ui_phrase('Delete') . ' ' . ui_phrase('Role')"
                                                :message="ui_phrase('confirm delete')"
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
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Roles')]) }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="md:hidden space-y-3">
            @forelse ($roles as $role)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $role->name }}</p>
                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:bg-slate-700/40 dark:text-slate-200">
                            {{ ui_phrase('permissions short', ['count' => $role->permissions->count()]) }}
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-1">
                        @forelse ($role->permissions->take(4) as $permission)
                            <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $permission->name }}</span>
                        @empty
                            <span class="text-xs text-gray-500 dark:text-gray-400">-</span>
                        @endforelse
                        @if ($role->permissions->count() > 4)
                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700/40 dark:text-gray-300">
                                {{ ui_phrase('more', ['count' => $role->permissions->count() - 4]) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('roles.edit', $role) }}" class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}">
                            <i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span>
                        </a>
                        <a href="{{ route('roles.create', ['template_role_id' => $role->id]) }}" class="btn-outline-sm" title="{{ ui_phrase('Clone') }}" aria-label="{{ ui_phrase('Clone') }}">
                            <i class="fa-solid fa-copy"></i><span class="sr-only">{{ ui_phrase('Clone') }}</span>
                        </a>
                        @can('module.role_manager.delete')
                            <x-ui.confirm-action
                                :action="route('roles.destroy', $role)"
                                method="DELETE"
                                :modal-name="'roles-index-delete-mobile-' . $role->id"
                                :title="ui_phrase('Delete') . ' ' . ui_phrase('Role')"
                                :message="ui_phrase('confirm delete')"
                                :notice-message="__('confirm.notification_after_action')"
                                notice-tone="danger"
                                :confirm-label="ui_phrase('Delete')"
                                :trigger-label="ui_phrase('Delete')"
                                trigger-icon="fa-solid fa-trash"
                                trigger-class="btn-danger-sm"
                                confirm-class="btn-danger-sm"
                            />
                        @endcan
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Roles')]) }}
                </div>
            @endforelse
        </div>

        <div>{{ $roles->links() }}</div>
    </div>
</div>

