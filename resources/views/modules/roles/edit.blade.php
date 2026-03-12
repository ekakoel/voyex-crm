@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')
                @include('modules.roles._form', [
                    'role' => $role,
                    'buttonLabel' => 'Update Role',
                    'selectedPermissions' => old('permissions', $selectedPermissions),
                ])
            </form>
        </div>
    </div>
@endsection




