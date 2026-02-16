@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Sales Target</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update the sales target.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('admin.salestargets.update', $salesTarget) }}">
                @csrf
                @method('PUT')
                @include('admin.salestargets._form', [
                    'salesTarget' => $salesTarget,
                    'buttonLabel' => 'Update Target',
                ])
            </form>
        </div>
    </div>
@endsection
