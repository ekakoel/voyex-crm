@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                @include('modules.roles._form', [
                    'buttonLabel' => 'Save Role',
                    'selectedPermissions' => old('permissions', []),
                ])
            </form>
        </div>
    </div>
@endsection




