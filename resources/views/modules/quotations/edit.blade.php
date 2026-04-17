@extends('layouts.master')

@section('page_title', __('ui.modules.quotations.edit_page_title'))
@section('page_subtitle', __('ui.modules.quotations.edit_page_subtitle'))
@section('page_actions')
    @if (($canValidateQuotation ?? false) === true)
        <a href="{{ route('quotations.validate.show', $quotation) }}" class="btn-outline">{{ __('ui.modules.quotations.validate_quotation') }}</a>
    @endif
    <a href="{{ route('quotations.show', $quotation) }}" class="btn-secondary">{{ __('ui.common.view_detail') }}</a>
    <a href="{{ route('quotations.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        <div class="module-grid-9-3">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('quotations.update', $quotation) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.quotations._form', [
                            'quotation' => $quotation,
                            'buttonLabel' => __('ui.modules.quotations.update_quotation'),
                        ])
                    </form>
                </div>
            </div>

            <aside class="module-grid-side">
                @if ($quotation->itinerary?->inquiry)
                    <div class="module-card p-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.inquiry_detail') }}</p>
                        <dl class="space-y-1 text-xs text-gray-700 dark:text-gray-200">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.inquiry_no') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->inquiry_number ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.customer') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->customer?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->status ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.priority') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->priority ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.source') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->source ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.assigned') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->assignedUser?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.deadline') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->deadline?->format('Y-m-d') ?? '-' }}</dd>
                            </div>
                            <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.notes') }}</dt>
                                @php
                                    $inquiryNotesHtml = \App\Support\SafeRichText::sanitize((string) ($quotation->itinerary?->inquiry?->notes ?? ''));
                                @endphp
                                <dd class="mt-1 text-gray-700 dark:text-gray-200">
                                    @if ($inquiryNotesHtml !== '')
                                        <div class="prose prose-sm max-w-none dark:prose-invert">{!! $inquiryNotesHtml !!}</div>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endif

                @if ($quotation->itinerary)
                    <div class="module-card p-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.itinerary_info') }}</p>
                        <dl class="space-y-1 text-xs text-gray-700 dark:text-gray-200">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.itinerary') }}</dt>
                                <dd class="font-medium text-right">#{{ $quotation->itinerary->id }} - {{ $quotation->itinerary->title }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.created_by') }}</dt>
                                <dd class="font-medium text-right"><x-masked-user-name :user="$quotation->itinerary?->creator" /></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.created_at') }}</dt>
                                <dd class="font-medium text-right"><x-local-time :value="$quotation->itinerary?->created_at" /></dd>
                            </div>
                        </dl>
                    </div>
                @endif

                <div class="module-card p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.activity_timeline') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.detailed_audit_log') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
                @include('partials._quotation-comments', ['quotation' => $quotation])

                <div class="module-card p-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('ui.common.approval') }}</p>
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.approval') }}</h2>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('ui.common.status') }}: {{ $quotation->status }}</p>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2">
                            <div class="flex items-center justify-between rounded-md border px-3 py-2 text-xs {{ ($approvalProgress['is_ready'] ?? false) ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300' }}">
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-flex rounded-full border border-current px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ __('ui.common.rule') }}</span>
                                    <span>{{ __('ui.modules.quotations.minimum_two_non_creator') }}</span>
                                </span>
                                <span>{{ (int) ($approvalProgress['non_creator_approval_count'] ?? 0) }}/{{ (int) ($approvalProgress['required_non_creator_approvals'] ?? 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md border px-3 py-2 text-xs {{ ((int) ($approvalProgress['remaining_non_creator_approvals'] ?? 0) === 0) ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300' }}">
                                <span>{{ __('ui.common.remaining_approvals') }}</span>
                                <span>{{ (int) ($approvalProgress['remaining_non_creator_approvals'] ?? 0) }}</span>
                            </div>
                        </div>

                        @if (!empty($approvalProgress['missing_labels']))
                            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                {{ __('ui.common.waiting_for', ['names' => implode(', ', $approvalProgress['missing_labels'])]) }}
                            </p>
                        @endif

                        @if ($quotation->approvals->isNotEmpty())
                            <div class="mt-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.approval_log') }}</p>
                                <ul class="mt-2 space-y-1 text-xs text-gray-700 dark:text-gray-200">
                                    @foreach ($quotation->approvals as $approval)
                                        <li>
                                            {{ ucfirst((string) $approval->approval_role) }} - <x-masked-user-name :user="$approval->user" />
                                            @if ($approval->approved_at)
                                                (<x-local-time :value="$approval->approved_at" />)
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (!empty($quotation->approval_note))
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                <p class="mt-1">{{ $quotation->approval_note }}</p>
                                <div class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-200/80">
                                    {{ __('ui.common.updated_by') }}{{ $quotation->approvalNoteBy ? ' - ' . $quotation->approvalNoteBy->displayNameFor(auth()->user(), 'System') : '' }}
                                    @if ($quotation->approval_note_at)
                                        | <x-local-time :value="$quotation->approval_note_at" />
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if (($quotation->status ?? '') === 'approved')
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <div><span class="font-medium text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.approved_at') }}:</span> <x-local-time :value="$quotation->approved_at" /></div>
                                <div><span class="font-medium text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.approved_by') }}:</span> {{ $quotation->approvedBy?->name ?? '-' }}</div>
                            </div>
                        @endif

                        @can('quotations.global_discount')
                            <form method="POST" action="{{ route('quotations.global-discount', $quotation) }}" class="mt-3 space-y-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                @csrf
                                @method('PATCH')
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.global_discount') }}</p>
                                <div class="grid grid-cols-1 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.discount_type') }}</label>
                                        <select name="global_discount_type" class="mt-1 app-input">
                                            <option value="">-</option>
                                            <option value="percent" @selected(old('global_discount_type', $quotation->discount_type ?? '') === 'percent')>{{ __('ui.common.percent') }}</option>
                                            <option value="fixed" @selected(old('global_discount_type', $quotation->discount_type ?? '') === 'fixed')>{{ __('ui.common.fixed') }}</option>
                                        </select>
                                        @error('global_discount_type')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <x-money-input
                                            label="{{ __('ui.common.discount_value') }}"
                                            name="global_discount_value"
                                            :value="old('global_discount_value', $quotation->discount_value ?? 0)"
                                            step="0.01"
                                        />
                                        @error('global_discount_value')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <button type="submit" class="btn-outline-sm">{{ __('ui.common.update_global_discount') }}</button>
                            </form>
                        @endcan

                        @if (auth()->check() && (auth()->user()->can('quotations.approve') || $quotation->isCreator(auth()->user()) || auth()->user()->can('quotations.reject') || auth()->user()->can('quotations.set_pending')))
                            <div class="mt-3 space-y-3">
                                @php
                                    $authUser = auth()->user();
                                    $isCreator = $quotation->isCreator($authUser);
                                    $alreadyApprovedByUser = $authUser
                                        ? $quotation->approvals->contains(fn ($a) => (int) ($a->user_id ?? 0) === (int) $authUser->id)
                                        : false;
                                    $requiredApprovals = (int) ($approvalProgress['required_non_creator_approvals'] ?? 2);
                                    $nonCreatorApprovalCount = (int) ($approvalProgress['non_creator_approval_count'] ?? 0);
                                    $canApproveByRole = false;
                                    if (!$isCreator && !$alreadyApprovedByUser && $authUser) {
                                        $canApproveByRole = $authUser->can('quotations.approve')
                                            && $nonCreatorApprovalCount < $requiredApprovals;
                                    }
                                    $isValidationComplete = (bool) ($validationProgress['is_complete'] ?? false);
                                    $requiresValidation = (bool) ($validationProgress['requires_validation'] ?? false);
                                    $canApproveWithValidation = $canApproveByRole && (! $requiresValidation || $isValidationComplete);
                                @endphp
                                <div class="flex items-center gap-2">
                                    @if (($quotation->status ?? '') !== 'approved' && ($quotation->status ?? '') !== 'final')
                                        @if ($canApproveWithValidation)
                                            <form method="POST" action="{{ route('quotations.approve', $quotation) }}">
                                                @csrf
                                                <button type="submit" class="btn-primary-sm">{{ __('ui.common.approve') }}</button>
                                            </form>
                                        @endif
                                        @can('quotations.reject')
                                            <button type="button" class="btn-danger-sm" data-open-reject-modal="edit-reject-modal">{{ __('ui.common.reject') }}</button>
                                        @endcan
                                    @else
                                        @if (($quotation->status ?? '') === 'approved' && $quotation->isCreator(auth()->user()))
                                            <form method="POST" action="{{ route('quotations.set-final', $quotation) }}">
                                                @csrf
                                                <button type="submit" class="btn-primary-sm">
                                                    {{ __('ui.common.set_final') }}
                                                </button>
                                            </form>
                                        @endif
                                        @if (($quotation->status ?? '') === 'approved' && auth()->user()->can('quotations.set_pending'))
                                            <form method="POST" action="{{ route('quotations.set-pending', $quotation) }}">
                                                @csrf
                                                <button type="submit" class="btn-warning-sm">
                                                    {{ __('ui.common.set_pending') }}
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                                @if (! $canApproveByRole && ($quotation->status ?? '') !== 'approved' && ($quotation->status ?? '') !== 'final')
                                    <p class="text-xs text-amber-700 dark:text-amber-300">
                                        @if ($alreadyApprovedByUser)
                                            Anda sudah melakukan approval pada quotation ini.
                                        @else
                                            Approval is not available because quorum has been met or you are not a valid approver.
                                        @endif
                                    </p>
                                @endif
                                @if ($canApproveByRole && ! $canApproveWithValidation && ($quotation->status ?? '') !== 'approved' && ($quotation->status ?? '') !== 'final')
                                    <p class="text-xs text-rose-600 dark:text-rose-300">
                                        {{ __('ui.modules.quotations.approval_requires_validation') }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if (auth()->user()->can('quotations.reject') && ($quotation->status ?? '') !== 'approved' && ($quotation->status ?? '') !== 'final')
                            <div id="edit-reject-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
                                <div class="w-full max-w-lg rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.quotations.reject_quotation') }}</h3>
                                        <button type="button" class="btn-ghost px-2 py-1 text-xs" data-close-reject-modal="edit-reject-modal">{{ __('ui.common.close') }}</button>
                                    </div>
                                    <form method="POST" action="{{ route('quotations.reject', $quotation) }}" class="mt-3 space-y-3">
                                        @csrf
                                        <div>
                                            <label class="block text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.reason_note') }}</label>
                                            <textarea
                                                name="approval_note"
                                                rows="4"
                                                class="mt-1 w-full app-input"
                                                placeholder="{{ __('ui.modules.quotations.reject_placeholder') }}"
                                                required
                                            >{{ old('approval_note') }}</textarea>
                                            @error('approval_note')
                                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" class="btn-secondary-sm" data-close-reject-modal="edit-reject-modal">{{ __('ui.common.cancel') }}</button>
                                            <button type="submit" class="btn-danger-sm">{{ __('ui.common.confirm_reject') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="module-card p-6 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.quotations.validation_progress') }}</h3>
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ (string) ($validationProgress['status'] ?? 'pending') === 'valid' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : ((string) ($validationProgress['status'] ?? 'pending') === 'partial' ? 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300') }}">
                            {{ (string) ($validationProgress['status'] ?? 'pending') }}
                        </span>
                    </div>

                    <div class="space-y-2 text-xs text-gray-700 dark:text-gray-200">
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.total_required_validation') }}</span>
                            <span class="font-medium">{{ (int) ($validationProgress['total_required'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.total_validated_items') }}</span>
                            <span class="font-medium">{{ (int) ($validationProgress['total_validated'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.validation_progress') }}</span>
                            <span class="font-medium">{{ (int) ($validationProgress['validation_percent'] ?? 0) }}%</span>
                        </div>
                    </div>

                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full bg-emerald-500 transition-all"
                            style="width: {{ max(0, min(100, (int) ($validationProgress['validation_percent'] ?? 0))) }}%;"
                        ></div>
                    </div>

                    @if (($canValidateQuotation ?? false) === true)
                        <a href="{{ route('quotations.validate.show', $quotation) }}" class="btn-outline w-full justify-center">
                            {{ __('ui.modules.quotations.validate_quotation') }}
                        </a>
                    @endif
                </div>
            </aside>
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
