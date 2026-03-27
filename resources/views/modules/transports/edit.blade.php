@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--transports">
        <div class="module-grid-9-3">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('transports.update', $transport) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.transports._form', ['transport' => $transport, 'buttonLabel' => 'Update Transport'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $transport])
            </aside>
        </div>
    </div>
@endsection


