@extends('layouts.master')

@section('page_title', 'Add Inquiry')
@section('page_subtitle', 'Create a new inquiry record.')
@section('page_actions')
    <a href="{{ route('inquiries.index') }}"  class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--inquiries">
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






