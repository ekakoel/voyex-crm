@extends('layouts.master')



@section('content')

    <div class="space-y-6 module-page module-page--quotations">

        @section('page_actions')<a href="{{ route('quotations.show', $quotation) }}"  class="btn-secondary">

                    View Detail

                </a>@endsection



        <div class="module-grid-9-3">

            <div class="module-grid-main">

                <div class="module-form-wrap">

                    <form method="POST" action="{{ route('quotations.update', $quotation) }}">

                        @csrf

                        @method('PUT')

                        @include('modules.quotations._form', [

                            'quotation' => $quotation,

                            'buttonLabel' => 'Update Quotation',

                            'showStatus' => false,

                        ])

                    </form>

                </div>

            </div>



            <aside  class="module-grid-side">

                @if ($quotation->itinerary?->inquiry)

                    <div class="module-card p-6 space-y-3 mb-4">

                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Inquiry Detail</p>

                        <dl class="space-y-1 text-xs text-gray-700 dark:text-gray-200">

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Inquiry No</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->inquiry_number ?? '-' }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Customer</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->customer?->name ?? '-' }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Status</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->status ?? '-' }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Priority</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->priority ?? '-' }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Source</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->source ?? '-' }}</dd>

                    <div class="rounded-lg mb-6 border

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Assigned</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->assignedUser?->name ?? '-' }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Deadline</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->deadline?->format('Y-m-d') ?? '-' }}</dd>

                            </div>

                            <div class="border-t border-gray-200 pt-2 dark:border-gray-700">

                                <dt class="text-gray-500 dark:text-gray-400">Notes</dt>

                                <dd class="mt-1 text-gray-700 dark:text-gray-200">{{ $quotation->itinerary?->inquiry?->notes ?? '-' }}</dd>

                            </div>

                        </dl>

                    </div>

                @endif



                @if ($quotation->itinerary)

                    <div class="module-card p-6 space-y-3 mb-4">

                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Itinerary Info</p>

                        <dl class="space-y-1 text-xs text-gray-700 dark:text-gray-200">

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Itinerary</dt>

                                <dd class="font-medium text-right">#{{ $quotation->itinerary->id }} - {{ $quotation->itinerary->title }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Created By</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->creator?->name ?? '-' }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Created At</dt>

                                <dd class="font-medium text-right">{{ $quotation->itinerary?->created_at?->format('Y-m-d H:i') ?? '-' }}</dd>

                            </div>

                        </dl>

                    </div>

                @endif



                <div class="mb-4">

                    @include('partials._audit-info', ['record' => $quotation, 'title' => 'Audit Info'])

                </div>



                <div class="mb-4">

                    @include('partials._quotation-comments', ['quotation' => $quotation])

                </div>



                <div class="module-card p-6 space-y-4">

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Validation</p>

                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">

                        <div class="flex flex-wrap items-center justify-between gap-3">

                            <div>

                                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Approval</h2>

                                <p class="text-xs text-gray-600 dark:text-gray-300">Status: {{ $quotation->status }}</p>

                            </div>

                        </div>

                        @if (!empty($quotation->approval_note))

                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">

                                

                                <p class="mt-1">{{ $quotation->approval_note }}</p>

                                <div class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-200/80">

                                    Updated by Director{{ $quotation->approvalNoteBy?->name ? ' - ' . $quotation->approvalNoteBy->name : '' }}

                                    @if ($quotation->approval_note_at)

                                        • {{ $quotation->approval_note_at->format('Y-m-d H:i') }}

                                    @endif

                                </div>

                            </div>

                        @endif

                        @if (($quotation->status ?? '') === 'approved')

                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">

                                <div><span class="font-medium text-gray-600 dark:text-gray-300">Approved At:</span> {{ $quotation->approved_at?->format('Y-m-d H:i') ?? '-' }}</div>

                                <div><span class="font-medium text-gray-600 dark:text-gray-300">Approved By:</span> {{ $quotation->approvedBy?->name ?? '-' }}</div>

                            </div>

                        @endif



                        @if (auth()->user()->hasAnyRole(['Director']))

                            <form method="POST" action="{{ route('quotations.approve', $quotation) }}" class="mt-3 space-y-3">

                                @csrf

                                <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">

                                    <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-200">

                                        <i class="fa-solid fa-comment-dots mr-2"></i> Add note

                                    </summary>

                                    <div class="mt-2">

                                        <label class="block text-xs text-gray-500 dark:text-gray-400">Validation Note</label>

                                        <textarea

                                            name="approval_note"

                                            rows="3"

                                            class="mt-1 w-full app-input"

                                            placeholder="Catatan terkait status yang akan dilakukan"

                                        >{{ old('approval_note', $quotation->approval_note ?? '') }}</textarea>

                                        @error('approval_note')

                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>

                                        @enderror

                                    </div>

                                </details>



                                <div class="flex items-center gap-2">

                                    @if (($quotation->status ?? '') !== 'approved')

                                        <button type="submit" formaction="{{ route('quotations.approve', $quotation) }}"   class="btn-primary-sm">Approve</button>

                                        <button type="submit" formaction="{{ route('quotations.reject', $quotation) }}"   class="btn-danger-sm">Reject</button>

                                    @else

                                        <button type="submit" formaction="{{ route('quotations.set-pending', $quotation) }}"   class="btn-warning-sm">

                                            Set Pending

                                        </button>

                                    @endif

                                </div>

                            </form>

                        @endif

                    </div>

                </div>

            </aside>

        </div>

    </div>

@endsection










