@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Template</h1>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('admin.quotation-templates.update', $quotationTemplate) }}">
                @csrf
                @method('PUT')
                @include('admin.quotation-templates._form', ['quotationTemplate' => $quotationTemplate, 'buttonLabel' => 'Update Template'])
            </form>
        </div>
    </div>
@endsection
