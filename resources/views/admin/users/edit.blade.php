@extends('layouts.master')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Employee</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update account data and roles for {{ $user->name }}.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('admin.users._form', [
                    'user' => $user,
                    'buttonLabel' => 'Update Employee',
                    'selectedRoles' => old('roles', $selectedRoles),
                ])
            </form>
        </div>
    </div>
@endsection
