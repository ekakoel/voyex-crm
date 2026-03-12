@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('food-beverages.update', $foodBeverage) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('modules.food-beverages._form', ['foodBeverage' => $foodBeverage, 'buttonLabel' => 'Update F&B'])
            </form>
        </div>
    </div>
@endsection


