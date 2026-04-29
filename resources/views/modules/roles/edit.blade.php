@extends('layouts.master')

@section('page_title', ui_phrase('edit page title'))
@section('page_subtitle', ui_phrase('edit page subtitle'))
@section('page_actions')
    <a href="{{ route('roles.index') }}" class="btn-secondary">
        {{ ui_phrase('Back to Roles') }}
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
                            'buttonLabel' => ui_phrase('Update Role'),
                            'selectedPermissions' => old('permissions', $selectedPermissions),
                            'selectedTemplateRoleId' => null,
                            'selectedTemplateRoleName' => null,
                        ])
                    </form>
                </div>
            </section>

            <aside class="module-grid-side">
                <div class="app-card p-6 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Role Info') }}</p>
                    <dl class="app-dl">
                        <div>
                            <dt>{{ ui_phrase('Current Role') }}</dt>
                            <dd>{{ $role->name }}</dd>
                        </div>
                        <div>
                            <dt>{{ ui_phrase('Guard') }}</dt>
                            <dd>{{ $role->guard_name ?? 'web' }}</dd>
                        </div>
                        <div>
                            <dt>{{ ui_phrase('Total Permission') }}</dt>
                            <dd>{{ count(old('permissions', $selectedPermissions ?? [])) }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
@endsection


