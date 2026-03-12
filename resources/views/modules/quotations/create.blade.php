@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--quotations">
        

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






