<div data-roles-index-results>
    <div class="space-y-4">
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

        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.role') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.permissions') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ __('ui.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($roles as $index => $role)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
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
                                            {{ __('ui.common.more', ['count' => $role->permissions->count() - 5]) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                                        <a href="{{ route('roles.create', ['template_role_id' => $role->id]) }}" class="btn-outline-sm"><i class="fa-solid fa-copy mr-1"></i>{{ __('ui.common.clone') }}</a>
                                        @can('module.role_manager.delete')
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('{{ __('ui.modules.roles.confirm_delete') }}')" class="btn-danger-sm">{{ __('ui.common.delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('ui.index.no_data_available', ['entity' => __('ui.entities.roles')]) }}
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
                            {{ __('ui.modules.roles.permissions_short', ['count' => $role->permissions->count()]) }}
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
                                {{ __('ui.common.more', ['count' => $role->permissions->count() - 4]) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('roles.edit', $role) }}" class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}">
                            <i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span>
                        </a>
                        <a href="{{ route('roles.create', ['template_role_id' => $role->id]) }}" class="btn-outline-sm">
                            <i class="fa-solid fa-copy mr-1"></i>{{ __('ui.common.clone') }}
                        </a>
                        @can('module.role_manager.delete')
                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('{{ __('ui.modules.roles.confirm_delete') }}')" class="btn-danger-sm">
                                    {{ __('ui.common.delete') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('ui.index.no_data_available', ['entity' => __('ui.entities.roles')]) }}
                </div>
            @endforelse
        </div>

        <div>{{ $roles->links() }}</div>
    </div>
</div>
