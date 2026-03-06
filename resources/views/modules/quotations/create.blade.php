@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--quotations">
        <div>
            <h1 class="app-section-title">Add Quotation</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create a new quotation from an inquiry.</p>
        </div>

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('quotations.store') }}">
                @csrf
                @include('modules.quotations._form', [
                    'buttonLabel' => 'Save Quotation',
                ])
            </form>
        </div>
    </div>
@endsection




