@extends('layouts.master')

@section('page_title', 'Create Employee')
@section('page_subtitle', 'Add a new user account and role assignment.')
@section('page_actions')
    <a href="{{ route('users.index') }}" class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--users">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf
                        @include('modules.users._form', [
                            'buttonLabel' => 'Save Employee',
                            'selectedRoles' => old('roles', []),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Lengkapi data login, role, dan status user sebelum menyimpan.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
