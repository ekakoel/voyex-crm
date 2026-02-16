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

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        [$modulePermissions, $otherPermissions, $permissionLabels] = $this->getPermissionsGrouped();

        return view('admin.roles.create', compact('modulePermissions', 'otherPermissions', 'permissionLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
            'custom_permission' => ['nullable', 'string', 'max:255'],
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

        $role->syncPermissions($permissions);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        [$modulePermissions, $otherPermissions, $permissionLabels] = $this->getPermissionsGrouped();
        $selectedPermissions = $role->permissions->pluck('name')->all();

        return view('admin.roles.edit', compact(
            'role',
            'modulePermissions',
            'otherPermissions',
            'permissionLabels',
            'selectedPermissions'
        ));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
            'custom_permission' => ['nullable', 'string', 'max:255'],
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

        $role->syncPermissions($permissions);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Admin') {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'The Admin role cannot be deleted.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
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

        foreach ($permissions as $permission) {
            if (str_starts_with($permission->name, 'module.')) {
                $parts = explode('.', $permission->name);
                $moduleKey = $parts[1] ?? 'unknown';
                $moduleName = $modules[$moduleKey] ?? $moduleKey;

                $modulePermissions[$moduleName][] = $permission->name;
                $permissionLabels[$permission->name] = "{$moduleName} Access";
            } else {
                $otherPermissions[] = $permission->name;
                $permissionLabels[$permission->name] = $this->humanizePermission($permission->name);
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
}
