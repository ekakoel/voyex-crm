@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Access Matrix</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Read-only matrix to audit role vs permission mapping across the system.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                <span class="rounded-md bg-gray-100 px-2 py-1 dark:bg-gray-700">Roles: {{ $roles->count() }}</span>
                <span class="rounded-md bg-gray-100 px-2 py-1 dark:bg-gray-700">Permissions: {{ $permissions->flatten()->count() }}</span>
            </div>

            <div class="space-y-5">
                @foreach ($permissions as $group => $groupPermissions)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="border-b border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                            {{ \Illuminate\Support\Str::of($group)->replace(['-', '_'], ' ')->title() }}
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[780px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-white dark:bg-gray-800">
                                    <tr>
                                        <th class="sticky left-0 z-10 bg-white px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:bg-gray-800 dark:text-gray-300">Permission</th>
                                        @foreach ($roles as $role)
                                            <th class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ $role->name }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($groupPermissions as $permission)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="sticky left-0 z-10 bg-white px-3 py-2 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                {{ $permission->name }}
                                            </td>
                                            @foreach ($roles as $role)
                                                @php
                                                    $hasAccess = isset($rolePermissionMap[$role->id][$permission->name]);
                                                @endphp
                                                <td class="px-3 py-2 text-center">
                                                    @if ($hasAccess)
                                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                            <i class="fa-solid fa-check text-[10px]"></i>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
                                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                                        </span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
