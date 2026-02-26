@extends('layouts.master')

@section('content')
    <div class="mx-auto w-full max-w-5xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Profile</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Manage your account settings, credentials, and security.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-xl border border-rose-200 bg-white p-6 shadow-sm dark:border-rose-800/60 dark:bg-gray-800">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection
