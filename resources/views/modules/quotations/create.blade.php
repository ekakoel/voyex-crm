@extends('layouts.master')

@section('page_title', 'Create Quotation')
@section('page_subtitle', 'Generate quotation from itinerary items and pricing rules.')
@section('page_actions')
    <a href="{{ route('quotations.index') }}" class="btn-ghost">Back</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
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




