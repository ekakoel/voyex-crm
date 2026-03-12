@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        
        <div class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <form method="POST" action="{{ route('vendors.update', $vendor) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.vendors._form', ['vendor' => $vendor, 'buttonLabel' => 'Update Vendor'])
                    </form>
                </div>
            </div>
            <aside class="xl:col-span-4">
                @include('partials._audit-info', ['record' => $vendor])
            </aside>
        </div>
    </div>
@endsection




