@extends('layouts.master')

@section('content')
    <div class="module-card p-6">
        

        <form method="POST" action="{{ route('currencies.store') }}" class="mt-6">
            @csrf
            @include('modules.currencies._form', ['buttonLabel' => 'Create'])
        </form>
    </div>
@endsection


