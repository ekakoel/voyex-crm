@php
    $buttonLabel = $buttonLabel ?? 'Save';
@endphp

<div class="space-y-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Role Name</label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $role->name ?? '') }}"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            required
        >
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="custom_permission" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Custom Permission</label>
        <input
            id="custom_permission"
            name="custom_permission"
            type="text"
            value="{{ old('custom_permission') }}"
            placeholder="example: reports.view"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
        >
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Fill in this field if you want to add a new permission outside of the existing modules.</p>
        @error('custom_permission')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <p class="block text-sm font-medium text-gray-700 dark:text-gray-200">Permissions per Module</p>
        <div class="mt-2 space-y-4">
            @forelse ($modulePermissions as $moduleName => $permissions)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $moduleName }}</p>
                    <div class="mt-3 rounded-lg border border-dashed border-gray-200 p-3 dark:border-gray-700">
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($permissions as $permission)
                                <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission }}"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        @checked(in_array($permission, old('permissions', $selectedPermissions ?? []), true))
                                    >
                                    <span class="text-gray-700 dark:text-gray-200">{{ $permissionLabels[$permission] ?? $permission }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No module permissions available yet.</p>
            @endforelse
        </div>
    </div>

    @if (! empty($otherPermissions))
        <div>
            <p class="block text-sm font-medium text-gray-700 dark:text-gray-200">Other Permissions</p>
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($otherPermissions as $permission)
                    <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission }}"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @checked(in_array($permission, old('permissions', $selectedPermissions ?? []), true))
                        >
                        <span class="text-gray-700 dark:text-gray-200">{{ $permissionLabels[$permission] ?? $permission }}</span>
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

    <div class="flex items-center gap-2">
        <button
            type="submit"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            {{ $buttonLabel }}
        </button>

        <a
            href="{{ route('admin.roles.index') }}"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        >
            Cancel
        </a>
    </div>
</div>
