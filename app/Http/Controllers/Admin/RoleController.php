<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $isSuperAdminActor = (bool) ($actor?->isSuperAdmin());
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $search = trim((string) $request->input('search', ''));

        $rolesBaseQuery = Role::query()
            ->with('permissions')
            ->when(! $isSuperAdminActor, fn ($query) => $query->where('name', '!=', 'Super Admin'));

        $roles = (clone $rolesBaseQuery)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhereHas('permissions', function ($permissionQuery) use ($search): void {
                            $permissionQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $statsCards = [
            [
                'key' => 'total_roles',
                'label' => 'Total',
                'value' => (int) (clone $rolesBaseQuery)->count(),
                'caption' => 'Roles',
                'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
            ],
            [
                'key' => 'filtered_roles',
                'label' => 'Visible',
                'value' => (int) $roles->total(),
                'caption' => $search !== '' ? 'Filtered result' : 'Current list',
                'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            ],
        ];

        if ($this->wantsAjaxFragment($request)) {
            return response()->json([
                'html' => view('modules.roles.partials._index-results', compact('roles'))->render(),
                'url' => route('roles.index', $request->query()),
            ]);
        }

        return view('modules.roles.index', compact('roles', 'search', 'statsCards'));
    }

    public function create(Request $request): View
    {
        $isSuperAdminActor = $this->isSuperAdminActor($request);
        [$modulePermissions, $otherPermissions, $permissionLabels] = $this->getPermissionsGrouped();
        [$templateRoles, $rolePermissionMap] = $this->getTemplateRoles($isSuperAdminActor);
        $selectedTemplateRoleId = $request->integer('template_role_id');
        $selectedTemplateRoleName = null;
        if (! $isSuperAdminActor && $selectedTemplateRoleId) {
            $selectedTemplateRoleId = null;
        }
        if ($selectedTemplateRoleId) {
            $selectedTemplateRoleName = $templateRoles
                ->firstWhere('id', $selectedTemplateRoleId)
                ?->name;
        }

        return view('modules.roles.create', compact(
            'modulePermissions',
            'otherPermissions',
            'permissionLabels',
            'templateRoles',
            'rolePermissionMap',
            'selectedTemplateRoleId',
            'selectedTemplateRoleName'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $isSuperAdminActor = $this->isSuperAdminActor($request);
        $validated = $request->validate([
            'name' => array_values(array_filter([
                'required',
                'string',
                'max:255',
                'unique:roles,name',
                ! $isSuperAdminActor ? Rule::notIn(['Super Admin']) : null,
            ])),
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
            'custom_permission' => ['nullable', 'string', 'max:255'],
            'template_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $permissions = $validated['permissions'] ?? [];
        $customPermission = trim((string) ($validated['custom_permission'] ?? ''));

        if ($customPermission !== '') {
            $permission = Permission::firstOrCreate([
                'name' => $customPermission,
                'guard_name' => 'web',
            ]);
            $permissions[] = $permission->name;
        }

        if (! $isSuperAdminActor) {
            $validated['template_role_id'] = null;
        }

        if (empty($permissions) && ! empty($validated['template_role_id'])) {
            $templateRole = Role::with('permissions')->find($validated['template_role_id']);
            if ($templateRole) {
                $permissions = $templateRole->permissions->pluck('name')->all();
            }
        }

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $isSuperAdminActor = $this->isSuperAdminActor();
        if (! $isSuperAdminActor && $role->name === 'Super Admin') {
            abort(404);
        }

        [$modulePermissions, $otherPermissions, $permissionLabels] = $this->getPermissionsGrouped();
        $selectedPermissions = $role->permissions->pluck('name')->all();
        [$templateRoles, $rolePermissionMap] = $this->getTemplateRoles($isSuperAdminActor);

        return view('modules.roles.edit', compact(
            'role',
            'modulePermissions',
            'otherPermissions',
            'permissionLabels',
            'selectedPermissions',
            'templateRoles',
            'rolePermissionMap'
        ));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $isSuperAdminActor = $this->isSuperAdminActor($request);
        if (! $isSuperAdminActor && $role->name === 'Super Admin') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => array_values(array_filter([
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id),
                ! $isSuperAdminActor ? Rule::notIn(['Super Admin']) : null,
            ])),
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
            'custom_permission' => ['nullable', 'string', 'max:255'],
            'template_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        $permissions = $validated['permissions'] ?? [];
        $customPermission = trim((string) ($validated['custom_permission'] ?? ''));

        if ($customPermission !== '') {
            $permission = Permission::firstOrCreate([
                'name' => $customPermission,
                'guard_name' => 'web',
            ]);
            $permissions[] = $permission->name;
        }

        if (! $isSuperAdminActor) {
            $validated['template_role_id'] = null;
        }

        if (empty($permissions) && ! empty($validated['template_role_id'])) {
            $templateRole = Role::with('permissions')->find($validated['template_role_id']);
            if ($templateRole) {
                $permissions = $templateRole->permissions->pluck('name')->all();
            }
        }

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $isSuperAdminActor = $this->isSuperAdminActor();
        if (! $isSuperAdminActor && $role->name === 'Super Admin') {
            abort(404);
        }

        if (in_array($role->name, ['Super Admin', 'Administrator'], true)) {
            $blockedLabel = $role->name === 'Super Admin' && ! $isSuperAdminActor ? 'This role' : $role->name;
            return redirect()
                ->route('roles.index')
                ->with('error', "The {$blockedLabel} role cannot be deleted.");
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    private function getPermissionsGrouped(): array
    {
        $modulePermissions = [];
        $otherPermissions = [];
        $permissionLabels = [];

        $permissions = Permission::query()->orderBy('name')->get();
        $modules = Schema::hasTable('modules')
            ? Module::query()->pluck('name', 'key')->all()
            : [];
        $permissionSet = $permissions->pluck('name')->flip();

        foreach ($permissions as $permission) {
            if (! str_starts_with($permission->name, 'module.')) {
                $otherPermissions[] = $permission->name;
                $permissionLabels[$permission->name] = $this->friendlyPermissionLabel($permission->name);
            }
        }

        foreach ($modules as $moduleKey => $moduleName) {
            $permissionsForModule = [];
            $actionLabels = [
                'access' => "{$moduleName} Access",
                'create' => "Create {$moduleName}",
                'read' => "Read {$moduleName}",
                'update' => "Update {$moduleName}",
                'delete' => "Delete {$moduleName}",
            ];

            foreach ($actionLabels as $action => $label) {
                $permissionName = "module.{$moduleKey}.{$action}";
                if (! isset($permissionSet[$permissionName])) {
                    continue;
                }
                $permissionsForModule[$action] = $permissionName;
                $permissionLabels[$permissionName] = $label;
            }

            if ($permissionsForModule !== []) {
                $modulePermissions[$moduleName] = [
                    'key' => $moduleKey,
                    'permissions' => $permissionsForModule,
                ];
            }
        }

        ksort($modulePermissions);

        return [$modulePermissions, $otherPermissions, $permissionLabels];
    }

    private function humanizePermission(string $name): string
    {
        $label = str_replace(['.', '_'], ' ', $name);
        $label = preg_replace('/\s+/', ' ', $label);
        return ucwords(trim($label));
    }

    private function friendlyPermissionLabel(string $name): string
    {
        $customLabels = [
            'services.map.view' => 'View Service Map',
            'superadmin.access_matrix.view' => 'View Access Matrix',
        ];

        return $customLabels[$name] ?? $this->humanizePermission($name);
    }

    private function getTemplateRoles(bool $isSuperAdminActor = false): array
    {
        $templateRoles = Role::query()
            ->with('permissions')
            ->when(! $isSuperAdminActor, fn ($query) => $query->where('name', '!=', 'Super Admin'))
            ->orderBy('name')
            ->get();

        $rolePermissionMap = $templateRoles
            ->mapWithKeys(fn (Role $role) => [$role->id => $role->permissions->pluck('name')->values()])
            ->all();

        return [$templateRoles, $rolePermissionMap];
    }

    private function wantsAjaxFragment(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Roles-Ajax') === '1';
    }

    private function isSuperAdminActor(?Request $request = null): bool
    {
        $request = $request ?: request();
        $user = $request?->user();

        return (bool) ($user?->isSuperAdmin());
    }
}
