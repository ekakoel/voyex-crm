@extends('layouts.master')

@section('page_title', 'Edit Inquiry')
@section('page_subtitle', 'Update inquiry details.')
@section('page_actions')
    <a href="{{ route('inquiries.show', $inquiry) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
        View Detail
    </a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--inquiries">
        <div class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8">
                <div class="module-form-wrap">
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
            <aside class="xl:col-span-4">
                @include('partials._audit-info', ['record' => $inquiry])
            </aside>
        </div>
    </div>
@endsection





