@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Add Inquiry</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create a new inquiry from a customer.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('sales.inquiries.store') }}">
                @csrf
                @include('sales.inquiries._form', [
                    'buttonLabel' => 'Save Inquiry',
                ])
            </form>
        </div>
    </div>
@endsection
