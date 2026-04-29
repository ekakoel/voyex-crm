@extends('layouts.master')

@section('page_title', ui_phrase('create page title'))
@section('page_subtitle', ui_phrase('create page subtitle'))
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
                    <form method="POST" action="{{ route('roles.store') }}">
                        @csrf
                        @include('modules.roles._form', [
                            'buttonLabel' => ui_phrase('Save Role'),
                            'selectedPermissions' => old('permissions', []),
                            'selectedTemplateRoleId' => $selectedTemplateRoleId ?? null,
                            'selectedTemplateRoleName' => $selectedTemplateRoleName ?? null,
                        ])
                    </form>
                </div>
            </section>

            <aside class="module-grid-side">
                <div class="app-card p-6 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Quick Guide') }}</p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>{{ ui_phrase('guide 1') }}</li>
                        <li>{{ ui_phrase('guide 2') }}</li>
                        <li>{{ ui_phrase('guide 3') }}</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
@endsection


