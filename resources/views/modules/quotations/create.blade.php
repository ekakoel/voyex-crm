@extends('layouts.master')

@section('page_title', ui_phrase('create page title'))
@section('page_subtitle', ui_phrase('create page subtitle'))
@section('page_actions')
    <a href="{{ route('quotations.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@push('scripts')
    @php
        $inquiryMapForJs = collect($inquiries ?? [])->mapWithKeys(function ($inquiry) {
            return [
                (string) $inquiry->id => [
                    'inquiry_number' => (string) ($inquiry->inquiry_number ?? '-'),
                    'customer_name' => (string) ($inquiry->customer?->name ?? '-'),
                    'status' => (string) ($inquiry->status ?? '-'),
                    'priority' => (string) ($inquiry->priority ?? '-'),
                    'source' => (string) ($inquiry->source ?? '-'),
                    'creator_name' => ui_user_name($inquiry->creator),
                    'deadline' => optional($inquiry->deadline)->format('Y-m-d') ?? '-',
                    'notes_html' => \App\Support\SafeRichText::sanitize((string) ($inquiry->notes ?? '')),
                ],
            ];
        })->all();
    @endphp
    <script>
        (function () {
            const itineraryMap = @json($itineraryInquiryMap ?? []);
            const inquiryMap = @json($inquiryMapForJs);
            const customerMap = @json(collect($customers ?? [])->mapWithKeys(function ($customer) {
                $label = trim((string) (($customer->company_name ?? '') !== '' ? $customer->company_name : ($customer->name ?? '')));
                return [(string) $customer->id => $label !== '' ? $label : '-'];
            })->all());
            const card = document.getElementById('quotation-create-inquiry-card');
            if (!card) return;

            const setField = (key, value) => {
                const el = card.querySelector(`[data-inquiry-field="${key}"]`);
                if (!el) return;
                el.textContent = value && String(value).trim() !== '' ? value : '-';
            };

            const setHtmlField = (key, value) => {
                const el = card.querySelector(`[data-inquiry-field="${key}"]`);
                if (!el) return;
                const html = value && String(value).trim() !== '' ? String(value) : '';
                el.innerHTML = html !== '' ? html : '-';
            };

            const renderInquiryCard = (data) => {
                if (!data) {
                    card.classList.add('hidden');
                    return;
                }

                setField('inquiry_number', data.inquiry_number);
                setField('customer_name', data.customer_name);
                setField('status', data.status);
                setField('priority', data.priority);
                setField('source', data.source);
                setField('creator_name', data.creator_name);
                setField('deadline', data.deadline);
                setHtmlField('notes', data.notes_html || '');
                card.classList.remove('hidden');
            };

            const renderBySelection = (itineraryId, inquiryId) => {
                if (inquiryId && inquiryMap[inquiryId]) {
                    renderInquiryCard(inquiryMap[inquiryId]);
                    return true;
                }
                if (!itineraryId || !itineraryMap[itineraryId]) {
                    const customerSelect = document.getElementById('customer-agent-select');
                    const selectedCustomerId = String(customerSelect?.value || '').trim();
                    const selectedCustomerName = selectedCustomerId !== '' ? (customerMap[selectedCustomerId] || '-') : '-';
                    if (selectedCustomerId === '') {
                        renderInquiryCard(null);
                        return false;
                    }
                    renderInquiryCard({
                        inquiry_number: '-',
                        customer_name: selectedCustomerName,
                        status: '-',
                        priority: '-',
                        source: '-',
                        creator_name: '-',
                        deadline: '-',
                        notes_html: '-',
                    });
                    return false;
                }

                renderInquiryCard(itineraryMap[itineraryId]);
                return true;
            };

            const itinerarySelect = document.getElementById('itinerary-select');
            const inquirySelect = document.getElementById('inquiry-select');
            const customerSelect = document.getElementById('customer-agent-select');
            if (itinerarySelect) {
                renderBySelection(itinerarySelect.value || '', inquirySelect?.value || '');
                itinerarySelect.addEventListener('change', () => {
                    renderBySelection(itinerarySelect.value || '', inquirySelect?.value || '');
                });
            }
            inquirySelect?.addEventListener('change', () => {
                renderBySelection(itinerarySelect?.value || '', inquirySelect.value || '');
            });
            customerSelect?.addEventListener('change', () => {
                renderBySelection(itinerarySelect?.value || '', inquirySelect?.value || '');
            });

            window.addEventListener('quotation:itinerary-selected', (event) => {
                const itineraryId = event?.detail?.itineraryId || '';
                renderBySelection(itineraryId, inquirySelect?.value || '');
            });
        })();
    </script>
@endpush

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="xl:col-span-9">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('quotations.store') }}">
                        @csrf
                        @include('modules.quotations._form', [
                            'buttonLabel' => ui_phrase('Save Quotation'),
                        ])
                    </form>
                </div>
            </div>
            <div class="space-y-6 xl:col-span-3">
                <div id="quotation-create-inquiry-card" class="module-card p-6 hidden">
                    <div class="mb-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Inquiry Detail') }}</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Inquiry No') }}:</p>
                            <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100" data-inquiry-field="inquiry_number">-</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer:') }}:</p>
                            <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100" data-inquiry-field="customer_name">-</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}:</p>
                            <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100" data-inquiry-field="status">-</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Priority') }}:</p>
                            <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100" data-inquiry-field="priority">-</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Source') }}:</p>
                            <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100" data-inquiry-field="source">-</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Created By') }}:</p>
                            <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100" data-inquiry-field="creator_name">-</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Deadline') }}:</p>
                            <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100" data-inquiry-field="deadline">-</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Notes') }}:</p>
                            <div class="prose prose-sm mt-1 max-w-none text-gray-700 dark:prose-invert dark:text-gray-200" data-inquiry-field="notes">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
