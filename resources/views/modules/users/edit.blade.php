@extends('layouts.master')

@section('page_title', ui_phrase('modules_users_edit_page_title'))
@section('page_subtitle', ui_phrase('modules_users_edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('users.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--users">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.users._form', [
                            'user' => $user,
                            'buttonLabel' => ui_phrase('modules_users_update_employee'),
                            'selectedRoles' => old('roles', $selectedRoles),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $user, 'title' => ui_phrase('common_audit_info')])
            </aside>
        </div>
    </div>
@endsection
