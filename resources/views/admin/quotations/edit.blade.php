@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Quotation</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update quotation {{ $quotation->quotation_number }}.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('admin.quotations.update', $quotation) }}">
                @csrf
                @method('PUT')
                @include('admin.quotations._form', [
                    'quotation' => $quotation,
                    'buttonLabel' => 'Update Quotation',
                ])
            </form>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Approval</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Status: {{ $quotation->approval_status }}</p>
                </div>
                @if (auth()->user()->hasAnyRole(['Admin']))
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.quotations.approve', $quotation) }}">
                            @csrf
                            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.quotations.reject', $quotation) }}">
                            @csrf
                            <button class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">Reject</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
