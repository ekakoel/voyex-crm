<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $query = User::query()
            ->with('roles:id,name')
            ->withoutSuperAdmin();

        $query->when(request('search'), function ($q) {
            $keyword = trim((string) request('search'));
            if ($keyword === '') {
                return;
            }
            $q->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        });

        $query->when(request('role'), function ($q) {
            $role = trim((string) request('role'));
            if ($role === '') {
                return;
            }
            $q->whereHas('roles', fn ($roles) => $roles->where('name', $role));
        });

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $users = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::query()
            ->where('name', '!=', 'Super Admin')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::query()
            ->where('name', '!=', 'Super Admin')
            ->orderBy('name')
            ->pluck('name');

        return view('modules.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name'), Rule::notIn(['Super Admin'])],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles($validated['roles']);

        return redirect()
            ->route('users.index')
            ->with('success', 'Employee created successfully.');
    }

    public function edit(User $user): View
    {
        if ($user->isSuperAdmin()) {
            abort(404);
        }

        $roles = Role::query()
            ->where('name', '!=', 'Super Admin')
            ->orderBy('name')
            ->pluck('name');
        $selectedRoles = $user->roles->pluck('name')->all();

        return view('modules.users.edit', compact('user', 'roles', 'selectedRoles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name'), Rule::notIn(['Super Admin'])],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles($validated['roles']);

        return redirect()
            ->route('users.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            abort(404);
        }

        $isCurrentUser = auth()->id() === $user->id;

        if ($isCurrentUser) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Employee deleted successfully.');
    }
}


