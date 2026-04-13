@extends('layouts.master')

@section('page_title', __('ui.modules.users.edit_page_title'))
@section('page_subtitle', __('ui.modules.users.edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('users.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
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
                            'buttonLabel' => __('ui.modules.users.update_employee'),
                            'selectedRoles' => old('roles', $selectedRoles),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $user, 'title' => __('ui.common.audit_info')])
            </aside>
        </div>
    </div>
@endsection
