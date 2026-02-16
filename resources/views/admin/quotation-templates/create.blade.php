@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Add Template</h1>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('admin.quotation-templates.store') }}">
                @csrf
                @include('admin.quotation-templates._form', ['buttonLabel' => 'Save Template'])
            </form>
        </div>
    </div>
@endsection
