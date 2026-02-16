@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-5">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Role & Permissions</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">This page is used to manage roles and module access permissions, ensuring that each user can only access modules according to their assigned permissions.</p>
            </div>
            <a href="{{ route('admin.roles.create') }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Add Role
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="md:hidden space-y-3">
            @forelse ($roles as $role)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $role->name }}</p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @forelse ($role->permissions as $permission)
                            <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $permission->name }}</span>
                        @empty
                            <span class="text-xs text-gray-500 dark:text-gray-400">-</span>
                        @endforelse
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Edit</a>
                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this role?')" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/20">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    No roles available.
                </div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Permissions</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $role->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @forelse ($role->permissions as $permission)
                                    <span class="mr-1 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ $permission->name }}
                                    </span>
                                @empty
                                    -
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                    Edit
                                </a>

                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        onclick="return confirm('Are you sure you want to delete this role?')"
                                        class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No roles available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
