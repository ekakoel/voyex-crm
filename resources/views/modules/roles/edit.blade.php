@extends('layouts.master')

@section('page_title', 'Edit Role')
@section('page_subtitle', 'Perbarui role dan konfigurasi akses permission.')
@section('page_actions')
    <a href="{{ route('roles.index') }}" class="btn-secondary">
        Back to Roles
    </a>
@endsection

@section('content')
    <div class="space-y-6 module-page">
        <div class="module-grid-9-3">
            <section class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('roles.update', $role) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.roles._form', [
                            'role' => $role,
                            'buttonLabel' => 'Update Role',
                            'selectedPermissions' => old('permissions', $selectedPermissions),
                            'selectedTemplateRoleId' => null,
                            'selectedTemplateRoleName' => null,
                        ])
                    </form>
                </div>
            </section>

            <aside class="module-grid-side">
                <div class="app-card p-6 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Role Info</p>
                    <dl class="app-dl">
                        <div>
                            <dt>Current Role</dt>
                            <dd>{{ $role->name }}</dd>
                        </div>
                        <div>
                            <dt>Guard</dt>
                            <dd>{{ $role->guard_name ?? 'web' }}</dd>
                        </div>
                        <div>
                            <dt>Total Permission</dt>
                            <dd>{{ count(old('permissions', $selectedPermissions ?? [])) }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
@endsection




