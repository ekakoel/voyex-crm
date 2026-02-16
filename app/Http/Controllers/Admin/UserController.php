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
        $users = User::query()
            ->with('roles:id,name')
            ->latest()
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::query()->orderBy('name')->pluck('name');

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles($validated['roles']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Employee created successfully.');
    }

    public function edit(User $user): View
    {
        $roles = Role::query()->orderBy('name')->pluck('name');
        $selectedRoles = $user->roles->pluck('name')->all();

        return view('admin.users.edit', compact('user', 'roles', 'selectedRoles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
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
            ->route('admin.users.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $isCurrentUser = auth()->id() === $user->id;

        if ($isCurrentUser) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Employee deleted successfully.');
    }
}
