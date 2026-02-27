<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessMatrixController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $permission) => Str::before($permission->name, '.'));

        $rolePermissionMap = [];
        foreach ($roles as $role) {
            $rolePermissionMap[$role->id] = $role->permissions->pluck('name')->flip()->all();
        }

        return view('superadmin.access-matrix', [
            'roles' => $roles,
            'permissions' => $permissions,
            'rolePermissionMap' => $rolePermissionMap,
        ]);
    }
}
