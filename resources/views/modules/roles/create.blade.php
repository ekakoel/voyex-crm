@extends('layouts.master')

@section('page_title', ui_phrase('modules_roles_create_page_title'))
@section('page_subtitle', ui_phrase('modules_roles_create_page_subtitle'))
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
                    <form method="POST" action="{{ route('roles.store') }}">
                        @csrf
                        @include('modules.roles._form', [
                            'buttonLabel' => ui_phrase('modules_roles_save_role'),
                            'selectedPermissions' => old('permissions', []),
                            'selectedTemplateRoleId' => $selectedTemplateRoleId ?? null,
                            'selectedTemplateRoleName' => $selectedTemplateRoleName ?? null,
                        ])
                    </form>
                </div>
            </section>

            <aside class="module-grid-side">
                <div class="app-card p-6 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_quick_guide') }}</p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>{{ ui_phrase('modules_roles_guide_1') }}</li>
                        <li>{{ ui_phrase('modules_roles_guide_2') }}</li>
                        <li>{{ ui_phrase('modules_roles_guide_3') }}</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
@endsection


