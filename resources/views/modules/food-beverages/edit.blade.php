@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--food-beverages">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('food-beverages.update', $foodBeverage) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.food-beverages._form', ['foodBeverage' => $foodBeverage, 'buttonLabel' => 'Update F&B'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('modules.activities.partials._vendor-info', ['vendor' => $foodBeverage->vendor])
                @include('partials._audit-info', ['record' => $foodBeverage])
            </aside>
        </div>
    </div>
@endsection


