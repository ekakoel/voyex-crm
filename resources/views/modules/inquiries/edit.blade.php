@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--inquiries">
        <div>
            <h1 class="app-section-title">Edit Inquiry</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update inquiry {{ $inquiry->inquiry_number }}.</p>
        </div>

        <div class="module-form-wrap">
            <div class="mb-4 flex items-center justify-end">
                <a href="{{ route('inquiries.show', $inquiry) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    View Detail
                </a>
            </div>

            <form method="POST" action="{{ route('inquiries.update', $inquiry) }}">
                @csrf
                @method('PUT')
                @include('modules.inquiries._form', [
                    'inquiry' => $inquiry,
                    'buttonLabel' => 'Update Inquiry',
                ])
            </form>
        </div>
    </div>
@endsection



