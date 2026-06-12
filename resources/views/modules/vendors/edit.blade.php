@extends('layouts.master')

@section('page_title', ui_phrase('Edit Vendor'))
@section('page_subtitle', ui_phrase('Update vendor/provider profile safely.'))

@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        <x-ui.page-header :title="ui_phrase('Edit Vendor')" :subtitle="ui_phrase('Maintain vendor data consistency across operations and service modules.')">
            <x-slot:actions>
                <a href="{{ route('vendors.index') }}" class="btn-ghost">{{ ui_phrase('Back') }}</a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <form method="POST" action="{{ route('vendors.update', $vendor) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('modules.vendors._form', ['vendor' => $vendor, 'buttonLabel' => ui_phrase('Update Vendor')])
                </form>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $vendor])
            </aside>
        </div>
    </div>
@endsection
