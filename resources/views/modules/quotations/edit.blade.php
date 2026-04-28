@extends('layouts.master')

@section('page_title', ui_phrase('modules_quotations_edit_page_title'))
@section('page_subtitle', ui_phrase('modules_quotations_edit_page_subtitle'))
@section('page_actions')
    @if (($canValidateQuotation ?? false) === true)
        <a href="{{ route('quotations.validate.show', $quotation) }}" class="btn-outline">{{ ui_phrase('modules_quotations_validate_quotation') }}</a>
    @endif
    <a href="{{ route('quotations.show', $quotation) }}" class="btn-secondary">{{ ui_phrase('common_view_detail') }}</a>
    <a href="{{ route('quotations.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@push('scripts')
    <script>
        (function () {
            const itineraryMap = @json($itineraryInquiryMap ?? []);
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
                setField('assigned_user_name', data.assigned_user_name);
                setField('deadline', data.deadline);
                setHtmlField('notes', data.notes_html || '');
                card.classList.remove('hidden');
            };

            const renderByItineraryId = (itineraryId) => {
                if (!itineraryId || !itineraryMap[itineraryId]) {
                    renderInquiryCard(null);
                    return false;
                }

                renderInquiryCard(itineraryMap[itineraryId]);
                return true;
            };

            const itinerarySelect = document.getElementById('itinerary-select');
            if (itinerarySelect) {
                renderByItineraryId(itinerarySelect.value || '');
                itinerarySelect.addEventListener('change', () => {
                    renderByItineraryId(itinerarySelect.value || '');
                });
            }

            window.addEventListener('quotation:itinerary-selected', (event) => {
                const itineraryId = event?.detail?.itineraryId || '';
                renderByItineraryId(itineraryId);
            });
        })();
    </script>
@endpush

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        <div id="quotation-create-inquiry-card" class="module-card p-6 hidden">
            <div class="mb-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_quotations_inquiry_detail') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_inquiry_no') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100" data-inquiry-field="inquiry_number">-</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_customer') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100" data-inquiry-field="customer_name">-</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_status') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100" data-inquiry-field="status">-</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_priority') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100" data-inquiry-field="priority">-</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_inquiries_source') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100" data-inquiry-field="source">-</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_assigned') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100" data-inquiry-field="assigned_user_name">-</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700 md:col-span-2">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_deadline') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100" data-inquiry-field="deadline">-</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700 md:col-span-2">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_notes') }}</p>
                    <div class="prose prose-sm mt-1 max-w-none text-gray-700 dark:prose-invert dark:text-gray-200" data-inquiry-field="notes">-</div>
                </div>
            </div>
        </div>

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('quotations.update', $quotation) }}">
                @csrf
                @method('PUT')
                @include('modules.quotations._form', [
                    'quotation' => $quotation,
                    'buttonLabel' => ui_phrase('modules_quotations_update_quotation'),
                ])
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const openModal = (id) => {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = (id) => {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            };

            document.querySelectorAll('[data-open-reject-modal]').forEach((btn) => {
                btn.addEventListener('click', () => openModal(btn.getAttribute('data-open-reject-modal')));
            });

            document.querySelectorAll('[data-close-reject-modal]').forEach((btn) => {
                btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-reject-modal')));
            });

            document.querySelectorAll('[id$="-reject-modal"]').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal(modal.id);
                });
            });

            @if ($errors->has('approval_note'))
                openModal('edit-reject-modal');
            @endif
        })();
    </script>
@endpush
