@extends('layouts.master')

@section('page_title', ui_phrase('modules_roles_edit_page_title'))
@section('page_subtitle', ui_phrase('modules_roles_edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('roles.index') }}" class="btn-secondary">
        {{ ui_phrase('modules_roles_back_to_roles') }}
    </a>
@endsection

@section('content')
    <div class="space-y-6 module-page">
        <div class="module-grid-8-4">
            <section class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('roles.update', $role) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.roles._form', [
                            'role' => $role,
                            'buttonLabel' => ui_phrase('modules_roles_update_role'),
                            'selectedPermissions' => old('permissions', $selectedPermissions),
                            'selectedTemplateRoleId' => null,
                            'selectedTemplateRoleName' => null,
                        ])
                    </form>
                </div>
            </section>

            <aside class="module-grid-side">
                <div class="app-card p-6 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_roles_role_info') }}</p>
                    <dl class="app-dl">
                        <div>
                            <dt>{{ ui_phrase('modules_roles_current_role') }}</dt>
                            <dd>{{ $role->name }}</dd>
                        </div>
                        <div>
                            <dt>{{ ui_phrase('modules_roles_guard') }}</dt>
                            <dd>{{ $role->guard_name ?? 'web' }}</dd>
                        </div>
                        <div>
                            <dt>{{ ui_phrase('modules_roles_total_permission') }}</dt>
                            <dd>{{ count(old('permissions', $selectedPermissions ?? [])) }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
@endsection


