@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $selectedValues = old('permissions', $selectedPermissions ?? []);
    $selectedValues = is_array($selectedValues) ? $selectedValues : [];
    $selectedTemplateRoleId = old('template_role_id', $selectedTemplateRoleId ?? null);
    $selectedTemplateRoleName = $selectedTemplateRoleName ?? null;
    $nameValue = old('name', $role->name ?? '');
    if ($nameValue === '' && $selectedTemplateRoleName) {
        $nameValue = "{$selectedTemplateRoleName} Copy";
    }
@endphp

<div class="space-y-6">
    <div class="app-card p-6">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="space-y-1.5">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Role Name</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ $nameValue }}"
                class="app-input"
                required
            >
            @error('name')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1.5">
            <label for="template_role_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Use Template Role</label>
            <select
                id="template_role_id"
                name="template_role_id"
                class="app-input"
            >
                <option value="">- None -</option>
                @foreach (($templateRoles ?? []) as $templateRole)
                    <option value="{{ $templateRole->id }}" @selected((string) $selectedTemplateRoleId === (string) $templateRole->id)>
                        {{ $templateRole->name }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400">Select a template role to auto-select permissions, then adjust as needed.</p>
            @error('template_role_id')
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
                class="app-input"
            >
            <p class="text-xs text-gray-500 dark:text-gray-400">Use this only for permissions outside the available modules.</p>
            @error('custom_permission')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    </div>

    <div class="app-card p-6">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Permissions per Module</p>
            <div class="flex items-center gap-2">
                <button type="button" id="selectAllPermissions"  class="btn-secondary-sm">Select all</button>
                <button type="button" id="clearAllPermissions"  class="btn-secondary-sm">Clear all</button>
                <span id="selectedPermissionsCount" class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    {{ count($selectedValues) }} selected
                </span>
            </div>
        </div>
        <div class="space-y-3">
            @forelse ($modulePermissions as $moduleName => $moduleData)
                @php
                    $modulePermissionsList = $moduleData['permissions'] ?? [];
                    $moduleSelectedCount = count(array_filter($modulePermissionsList, fn ($permission) => in_array($permission, $selectedValues, true)));
                    $permissionActions = [
                        'access' => 'Access',
                        'create' => 'Create',
                        'read' => 'Read',
                        'update' => 'Update',
                        'delete' => 'Delete',
                    ];
                @endphp
                <section class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800" data-module-card data-module-key="{{ $moduleData['key'] ?? '' }}">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $moduleName }}</p>
                            <span class="module-crud-badge hidden rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Full CRUD</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"  class="btn-secondary-sm" data-module-toggle="all">All</button>
                            <button type="button"  class="btn-secondary-sm" data-module-toggle="none">None</button>
                            <span class="module-counter text-xs text-gray-500 dark:text-gray-400">{{ $moduleSelectedCount }}/{{ count($modulePermissionsList) }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($permissionActions as $action => $label)
                            @php
                                $permissionName = $modulePermissionsList[$action] ?? null;
                            @endphp
                            @if ($permissionName)
                                <label class="inline-flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/40">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permissionName }}"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        @checked(in_array($permissionName, $selectedValues, true))
                                        data-module-action="{{ $action }}"
                                    >
                                    <span>{{ $permissionLabels[$permissionName] ?? $label }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No module permissions available yet.</p>
            @endforelse
        </div>
    </div>

    @if (! empty($otherPermissions))
        <div class="app-card p-6">
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
            class="btn-primary"
        >
            {{ $buttonLabel }}
        </button>

        <a
            href="{{ route('roles.index') }}"
            class="btn-secondary"
        >
            Cancel
        </a>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const selectedCountEl = document.getElementById('selectedPermissionsCount');
        const templateSelect = document.getElementById('template_role_id');
        const nameInput = document.getElementById('name');
        const selectAllButton = document.getElementById('selectAllPermissions');
        const clearAllButton = document.getElementById('clearAllPermissions');
        const permissionInputs = Array.from(document.querySelectorAll('input[name="permissions[]"]'));
        const permissionsByRole = @json($rolePermissionMap ?? []);
        const templateNameById = @json(collect($templateRoles ?? [])->mapWithKeys(fn ($role) => [$role->id => $role->name])->all());
        let nameTouched = false;

        const updateSelectedCount = () => {
            if (!selectedCountEl) return;
            const count = permissionInputs.filter(input => input.checked).length;
            selectedCountEl.textContent = `${count} selected`;
        };

        const setPermissions = (permissions) => {
            const set = new Set(permissions || []);
            permissionInputs.forEach(input => {
                input.checked = set.has(input.value);
            });
            updateSelectedCount();
            updateAllModuleBadges();
        };

        const setNameFromTemplate = (roleId) => {
            if (!nameInput) return;
            if (nameTouched && nameInput.value.trim() !== '') return;
            const roleName = templateNameById[roleId];
            if (!roleName) return;
            nameInput.value = `${roleName} Copy`;
        };

        if (nameInput) {
            nameInput.addEventListener('input', () => {
                nameTouched = true;
            });
        }

        permissionInputs.forEach(input => {
            input.addEventListener('change', updateSelectedCount);
        });

        const moduleCards = Array.from(document.querySelectorAll('[data-module-card]'));

        const updateModuleBadge = (card) => {
            const badge = card.querySelector('.module-crud-badge');
            if (!badge) return;
            const access = card.querySelector('input[data-module-action="access"]');
            const create = card.querySelector('input[data-module-action="create"]');
            const read = card.querySelector('input[data-module-action="read"]');
            const update = card.querySelector('input[data-module-action="update"]');
            const del = card.querySelector('input[data-module-action="delete"]');
            const allChecked = [access, create, read, update, del].every(input => input && input.checked);
            badge.classList.toggle('hidden', !allChecked);
        };

        const updateModuleCounter = (card) => {
            const counter = card.querySelector('.module-counter');
            if (!counter) return;
            const inputs = Array.from(card.querySelectorAll('input[data-module-action]'));
            const checked = inputs.filter(input => input.checked).length;
            counter.textContent = `${checked}/${inputs.length}`;
        };

        const updateModuleAccess = (card) => {
            const accessInput = card.querySelector('input[data-module-action="access"]');
            if (!accessInput) return;
            const otherInputs = Array.from(card.querySelectorAll('input[data-module-action]'))
                .filter(input => input !== accessInput);
            const anyOtherChecked = otherInputs.some(input => input.checked);
            if (!anyOtherChecked && accessInput.checked) {
                accessInput.checked = false;
            }
            if (anyOtherChecked && !accessInput.checked) {
                accessInput.checked = true;
            }
        };

        const updateAllModuleBadges = () => {
            moduleCards.forEach((card) => {
                updateModuleBadge(card);
                updateModuleCounter(card);
                updateModuleAccess(card);
            });
        };

        moduleCards.forEach((card) => {
            const accessInput = card.querySelector('input[data-module-action="access"]');
            const readInput = card.querySelector('input[data-module-action="read"]');
            const moduleInputs = Array.from(card.querySelectorAll('input[data-module-action]'));
            const toggleButtons = card.querySelectorAll('[data-module-toggle]');

            if (accessInput && readInput) {
                accessInput.addEventListener('change', () => {
                    if (accessInput.checked && !readInput.checked) {
                        readInput.checked = true;
                    }
                    updateSelectedCount();
                    updateModuleBadge(card);
                    updateModuleCounter(card);
                });
            }

            moduleInputs.forEach(input => {
                input.addEventListener('change', () => {
                    if (input.dataset.moduleAction !== 'access') {
                        updateModuleAccess(card);
                    }
                    updateSelectedCount();
                    updateModuleBadge(card);
                    updateModuleCounter(card);
                });
            });

            toggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const mode = button.dataset.moduleToggle;
                    moduleInputs.forEach(input => {
                        input.checked = mode === 'all';
                    });
                    if (mode === 'all' && accessInput && readInput && !readInput.checked) {
                        readInput.checked = true;
                    }
                    updateSelectedCount();
                    updateModuleBadge(card);
                    updateModuleCounter(card);
                });
            });
        });

        if (templateSelect) {
            templateSelect.addEventListener('change', (event) => {
                const roleId = event.target.value;
                if (!roleId) {
                    return;
                }
                setPermissions(permissionsByRole[roleId] || []);
                setNameFromTemplate(roleId);
            });
        }

        if (selectAllButton) {
            selectAllButton.addEventListener('click', () => {
                permissionInputs.forEach(input => {
                    input.checked = true;
                });
                updateSelectedCount();
            });
        }

        if (clearAllButton) {
            clearAllButton.addEventListener('click', () => {
                permissionInputs.forEach(input => {
                    input.checked = false;
                });
                updateSelectedCount();
                updateAllModuleBadges();
            });
        }

        updateAllModuleBadges();
    })();
</script>
@endpush




