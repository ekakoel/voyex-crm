@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('vendors.update', $vendor) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.vendors._form', ['vendor' => $vendor, 'buttonLabel' => 'Update Vendor'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $vendor])
            </aside>
        </div>
    </div>
@endsection




