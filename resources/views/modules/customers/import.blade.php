@extends('layouts.master')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Import Customers</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Upload a CSV to add customers in bulk.</p>
        </div>

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
            <form method="POST" action="{{ route('customers.import.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Download the CSV template for the standard format.</p>
                    <a href="{{ route('customers.import.template') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Download Template
                    </a>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">File CSV</label>
                    <input type="file" name="file" accept=".csv,text/csv" class="mt-1 w-full text-sm">
                    @error('file')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                    Header minimal: <code>name</code><br>
                    Header opsional: <code>code, email, phone, address, country, customer_type, company_name</code><br>
                    Allowed <code>customer_type</code> values: <code>individual</code> or <code>company</code>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Duplicate Mode</label>
                    <select name="mode" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        <option value="skip">Skip duplicates</option>
                        <option value="update">Update duplicates</option>
                    </select>
                    @error('mode')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Preview
                    </button>
                    <a href="{{ route('customers.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection


