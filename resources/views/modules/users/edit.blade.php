@extends('layouts.master')

@section('content')
    <div class="max-w-3xl space-y-6">
        

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
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
@endsection




