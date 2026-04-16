@extends('layouts.master')

@section('page_title', __('ui.modules.quotations.create_page_title'))
@section('page_subtitle', __('ui.modules.quotations.create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('quotations.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@push('scripts')
    <script>
        (function () {
            const map = @json($itineraryInquiryMap ?? []);
            const card = document.getElementById('quotation-create-inquiry-card');
            if (!card) return;

            const setField = (key, value) => {
                const el = card.querySelector(`[data-inquiry-field="${key}"]`);
                if (!el) return;
                el.textContent = value && String(value).trim() !== '' ? value : '-';
            };

            const renderByItineraryId = (itineraryId) => {
                if (!itineraryId || !map[itineraryId]) {
                    card.classList.add('hidden');
                    return;
                }

                const data = map[itineraryId];
                setField('inquiry_number', data.inquiry_number);
                setField('customer_name', data.customer_name);
                setField('status', data.status);
                setField('priority', data.priority);
                setField('source', data.source);
                setField('assigned_user_name', data.assigned_user_name);
                setField('deadline', data.deadline);
                setField('notes', data.notes);
                card.classList.remove('hidden');
            };

            const itinerarySelect = document.getElementById('itinerary-select');
            if (itinerarySelect) {
                renderByItineraryId(itinerarySelect.value || '');
                itinerarySelect.addEventListener('change', () => {
                    renderByItineraryId(itinerarySelect.value || '');
                });
            }

            window.addEventListener('quotation:itinerary-selected', (event) => {
                renderByItineraryId(event?.detail?.itineraryId || '');
            });
        })();
    </script>
@endpush

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        <div class="module-grid-9-3">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('quotations.store') }}">
                        @csrf
                        @include('modules.quotations._form', [
                            'buttonLabel' => __('ui.modules.quotations.save_quotation'),
                        ])
                    </form>
                </div>
            </div>

            <aside class="module-grid-side space-y-4">
                <div id="quotation-create-inquiry-card" class="module-card p-6 space-y-3 hidden">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.inquiry_detail') }}</p>
                    <dl class="space-y-1 text-xs text-gray-700 dark:text-gray-200">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.inquiry_no') }}</dt>
                            <dd class="font-medium text-right" data-inquiry-field="inquiry_number">-</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.customer') }}</dt>
                            <dd class="font-medium text-right" data-inquiry-field="customer_name">-</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}</dt>
                            <dd class="font-medium text-right" data-inquiry-field="status">-</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.priority') }}</dt>
                            <dd class="font-medium text-right" data-inquiry-field="priority">-</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.source') }}</dt>
                            <dd class="font-medium text-right" data-inquiry-field="source">-</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.assigned') }}</dt>
                            <dd class="font-medium text-right" data-inquiry-field="assigned_user_name">-</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.deadline') }}</dt>
                            <dd class="font-medium text-right" data-inquiry-field="deadline">-</dd>
                        </div>
                        <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.notes') }}</dt>
                            <dd class="mt-1 text-gray-700 dark:text-gray-200 whitespace-pre-wrap" data-inquiry-field="notes">-</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
@endsection


