@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Customer</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update customer data for {{ $customer->name }}.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('sales.customers.update', $customer) }}">
                @csrf
                @method('PUT')
                @include('sales.customers._form', [
                    'customer' => $customer,
                    'buttonLabel' => 'Update Customer',
                ])
            </form>
        </div>
    </div>
@endsection
