@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--inquiries">
        <div>
            <h1 class="app-section-title">Add Inquiry</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create a new inquiry from a customer.</p>
        </div>

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('inquiries.store') }}">
                @csrf
                @include('modules.inquiries._form', [
                    'buttonLabel' => 'Save Inquiry',
                ])
            </form>
        </div>
    </div>
@endsection



