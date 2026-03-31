@extends('layouts.master')

@section('page_title', 'Edit Employee')
@section('page_subtitle', 'Update user profile, access role, and account status.')
@section('page_actions')
    <a href="{{ route('users.index') }}" class="btn-ghost">Back</a>
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
                            'buttonLabel' => 'Update Employee',
                            'selectedRoles' => old('roles', $selectedRoles),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $user, 'title' => 'Audit Info'])
            </aside>
        </div>
    </div>
@endsection
