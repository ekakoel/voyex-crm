@extends('layouts.master')

@section('page_title', ui_phrase('Edit Itinerary'))
@section('page_subtitle', ui_phrase('Update itinerary information.'))
@section('page_actions')
    <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-secondary">{{ ui_phrase('View Detail') }}</a>
    <a href="{{ route('itineraries.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection


@section('content')
    <div class="space-y-5 module-page module-page--itineraries">
        @if (! empty($revisionQuotation))
            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-200">
                <div class="font-semibold">{{ ui_phrase('Quotation Revision Context') }}</div>
                <p class="mt-1">
                    {{ ui_phrase('This itinerary revision is linked to quotation') }}
                    <a href="{{ route('quotations.show', $revisionQuotation) }}" class="font-semibold underline">
                        {{ $revisionQuotation->quotation_number ?? ('#' . $revisionQuotation->id) }}
                    </a>.
                    {{ ui_phrase('After saving, continue revising quotation items and run revalidation before sending again.') }}
                </p>
            </div>
        @endif

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('itineraries.update', $itinerary) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="quotation_id" value="{{ (int) request('quotation_id', 0) }}">
                <input type="hidden" name="return_to_quotation_revise" value="{{ request()->boolean('return_to_quotation_revise') ? 1 : 0 }}">
                <input type="hidden" name="revision_mode" value="{{ request()->boolean('revision_mode') ? 1 : 0 }}">
                @include('modules.itineraries._form', [
                    'itinerary' => $itinerary,
                    'buttonLabel' => ui_phrase('Update Itinerary'),
                ])
            </form>
        </div>
    </div>
@endsection
