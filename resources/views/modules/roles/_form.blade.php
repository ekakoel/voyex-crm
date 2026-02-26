@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $selectedValues = old('permissions', $selectedPermissions ?? []);
    $selectedValues = is_array($selectedValues) ? $selectedValues : [];
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="space-y-1.5">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Role Name</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $role->name ?? '') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                required
            >
            @error('name')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1.5">
            <label for="custom_permission" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Custom Permission</label>
            <input
                id="custom_permission"
                name="custom_permission"
                type="text"
                value="{{ old('custom_permission') }}"
                placeholder="example: reports.view"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            >
            <p class="text-xs text-gray-500 dark:text-gray-400">Use this only for permission di luar module yang sudah tersedia.</p>
            @error('custom_permission')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-xl bg-gray-50/70 p-4 dark:bg-gray-900/30">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Permissions per Module</p>
            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                {{ count($selectedValues) }} selected
            </span>
        </div>
        <div class="space-y-3">
            @forelse ($modulePermissions as $moduleName => $permissions)
                @php
                    $moduleSelectedCount = count(array_filter($permissions, fn ($permission) => in_array($permission, $selectedValues, true)));
                @endphp
                <section class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $moduleName }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $moduleSelectedCount }}/{{ count($permissions) }}</span>
                    </div>
                    <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="inline-flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/40">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    @checked(in_array($permission, $selectedValues, true))
                                >
                                <span>{{ $permissionLabels[$permission] ?? $permission }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No module permissions available yet.</p>
            @endforelse
        </div>
    </div>

    @if (! empty($otherPermissions))
        <div class="rounded-xl bg-gray-50/70 p-4 dark:bg-gray-900/30">
            <p class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Other Permissions</p>
            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                @foreach ($otherPermissions as $permission)
                    <label class="inline-flex items-center gap-2 rounded-md bg-white px-2 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/40">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission }}"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @checked(in_array($permission, $selectedValues, true))
                        >
                        <span>{{ $permissionLabels[$permission] ?? $permission }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    @error('permissions')
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('permissions.*')
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror

    <div class="flex items-center gap-2 pt-1">
        <button
            type="submit"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            {{ $buttonLabel }}
        </button>

        <a
            href="{{ route('roles.index') }}"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        >
            Cancel
        </a>
    </div>
</div>


