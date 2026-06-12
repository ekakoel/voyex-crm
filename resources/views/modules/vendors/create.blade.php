@extends('layouts.master')

@section('page_title', ui_phrase('Create Vendor'))
@section('page_subtitle', ui_phrase('Add new vendor/provider master data for operation and reservation usage.'))

@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        <x-ui.page-header :title="ui_phrase('Create Vendor')" :subtitle="ui_phrase('Complete vendor profile, location, and contact information.')">
            <x-slot:actions>
                <a href="{{ route('vendors.index') }}" class="btn-ghost">{{ ui_phrase('Back') }}</a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <form method="POST" action="{{ route('vendors.store') }}" class="space-y-4">
                    @csrf
                    @include('modules.vendors._form', ['buttonLabel' => ui_phrase('Save Vendor')])
                </form>
            </div>
            <aside class="module-grid-side">
                <x-ui.info-card :title="ui_phrase('Vendor Guidance')">
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ ui_phrase('Use complete and accurate contact and location data so reservation can confirm services quickly.') }}</p>
                </x-ui.info-card>
            </aside>
        </div>
    </div>
@endsection
