@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--quotations">
        <div>
            <h1 class="app-section-title">Edit Quotation</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update quotation {{ $quotation->quotation_number }}.</p>
        </div>

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('quotations.update', $quotation) }}">
                @csrf
                @method('PUT')
                @include('modules.quotations._form', [
                    'quotation' => $quotation,
                    'buttonLabel' => 'Update Quotation',
                ])
            </form>
        </div>

        <div class="module-card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Approval</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Status: {{ $quotation->approval_status }}</p>
                </div>
                @if (auth()->user()->hasAnyRole(['Sales Manager', 'Director']))
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('quotations.approve', $quotation) }}">
                            @csrf
                            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('quotations.reject', $quotation) }}">
                            @csrf
                            <button class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">Reject</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection




