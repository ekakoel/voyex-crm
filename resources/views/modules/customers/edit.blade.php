@extends('layouts.master')

@section('page_title', 'Edit Customer')
@section('page_subtitle', 'Update customer details.')
@section('page_actions')
    <a href="{{ route('customers.index') }}"  class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--customers">
        <div class="module-grid-9-3">
            <div class="module-grid-main space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <form method="POST" action="{{ route('customers.update', $customer) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.customers._form', [
                            'customer' => $customer,
                            'buttonLabel' => 'Update Customer',
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $customer])
            </aside>
        </div>
    </div>
@endsection

