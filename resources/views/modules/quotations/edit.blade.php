@extends('layouts.master')

@php
    $isRevisionMode = (bool) ($isRevisionMode ?? false);
@endphp

@section('page_title', $isRevisionMode ? ui_phrase('Revise Quotation') : ui_phrase('edit page title'))
@section('page_subtitle', $isRevisionMode ? ui_phrase('Update customer requested changes and prepare the quotation to send again.') : ui_phrase('edit page subtitle'))
@section('page_actions')
    @if (($canValidateQuotation ?? false) === true)
        <x-quotation-action-button :href="route('quotations.validate.show', $quotation)" variant="outline" icon="fa-clipboard-check" label="Validate Quotation" />
    @endif
    <x-quotation-action-button :href="route('quotations.show', $quotation)" variant="outline" icon="fa-eye" label="View Detail" />
    <x-quotation-action-button :href="route('quotations.index')" variant="ghost" icon="fa-arrow-left" label="Back" data-page-back-action />
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
                    <form method="POST" action="{{ route('quotations.update', $quotation) }}">
                        @csrf
                        @method('PUT')
                        @if ($isRevisionMode)
                            <input type="hidden" name="revision_mode" value="1">
                        @endif
                        @include('modules.quotations._form', [
                            'quotation' => $quotation,
                            'buttonLabel' => $isRevisionMode ? ui_phrase('Save Revision') : ui_phrase('Update Quotation'),
                        ])
                    </form>
                </div>
            </div>
            <div class="space-y-6 xl:col-span-3">
                @if (($customerRequestedChanges ?? collect())->isNotEmpty())
                    <div class="module-card p-6">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Customer Requested Changes') }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Select the customer responses handled by this revision.') }}</p>
                        <form method="POST" action="{{ route('quotations.customer-responses.mark-selected-used-for-revision', $quotation) }}" class="mt-3 space-y-3 text-xs text-gray-700 dark:text-gray-200">
                            @csrf
                            <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                                @foreach ($customerRequestedChanges as $response)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-md border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-700 dark:bg-amber-900/20">
                                        <input
                                            type="checkbox"
                                            name="customer_response_ids[]"
                                            value="{{ $response->id }}"
                                            class="mt-1 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-900"
                                        >
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center justify-between gap-2">
                                                <span class="font-semibold">{{ ui_phrase('Revision Requested') }}</span>
                                                <span class="shrink-0 text-gray-500 dark:text-gray-400"><x-local-time :value="$response->response_at" /></span>
                                            </span>
                                            <span class="mt-1 block truncate text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ $response->response_channel ?? '-' }} - {{ $response->response_status ?? '-' }}
                                            </span>
                                            @if (!empty($response->response_note))
                                                <span class="mt-2 block text-gray-700 dark:text-gray-200">{{ $response->response_note }}</span>
                                            @endif
                                            <span class="mt-2 block text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('By') }}: {{ ui_user_name($response->creator) }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('customer_response_ids')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary-sm inline-flex items-center gap-2">
                                    <i class="fa-solid fa-check-double w-4 text-center"></i>
                                    <span>{{ ui_phrase('Mark Selected as Handled') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @elseif ($isRevisionMode)
                    <div class="module-card p-6">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Customer Requested Changes') }}</h3>
                        <div class="mt-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                            {{ ui_phrase('All customer revision responses are handled.') }}
                        </div>
                    </div>
                @endif
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
