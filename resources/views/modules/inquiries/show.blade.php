@extends('layouts.master')



@section('page_title', 'Inquiry Detail')

@section('page_subtitle', 'Review inquiry details.')

@section('page_actions')

    @can('update', $inquiry)

        @if (!($inquiry->quotation && ($inquiry->quotation->status ?? '') === 'approved') && ! $inquiry->isFinal())

            <a href="{{ route('inquiries.edit', $inquiry) }}"  class="btn-secondary">

                Edit Inquiry
            </a>
        @endif

    @endcan

@endsection



@section('content')

    <div class="max-w-6xl space-y-6 module-page module-page--inquiries">

        @if (session('success'))

            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">

                {{ session('success') }}

            </div>

        @endif



        @if ($errors->any())

            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">

                {{ $errors->first() }}

            </div>

        @endif



        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="lg:col-span-1 space-y-6">
                <div class="app-card p-5 mb-6">

                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Inquiry Overview</h2>

                    <dl class="app-dl" class="mt-4 space-y-3 text-sm">

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">Customer</dt>

                        <dd class="text-right font-medium text-gray-800 dark:text-gray-100">({{ $inquiry->customer->code ?? '-' }}) {{ $inquiry->customer->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>

                        <dd><x-status-badge :status="$inquiry->status" size="xs" /></dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">Priority</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->priority }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">Source</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $sourceLabels[$inquiry->source] ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">Assigned To</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->assignedUser->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">Deadline</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->deadline?->format('Y-m-d') ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">Reminder Email</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->reminder_enabled ? 'Enabled' : 'Disabled' }}</dd>

                    </div>

                    <div>

                        <div class="text-gray-500 dark:text-gray-400">Notes:</div>

                        <dd class="text-left font-medium text-gray-800 dark:text-gray-100">{!! $inquiry->notes ?: '-' !!}</dd>

                    </div>

                    </dl>

                </div>

                <div class="app-card p-6 space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Activity Timeline</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Tracking all changes for this inquiry.</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                    <div>{{ $activities->links() }}</div>
                </div>
            </div>



            <div class="lg:col-span-2 space-y-6">

                <div class="app-card p-6 space-y-4 mb-6">

                    <div class="flex mb-2 items-start justify-between gap-2">

                        <div>

                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Reminder Follow-up</h2>

                        </div>

                    </div>

                    <div class="overflow-x-auto app-card">

                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">

                            <thead>

                                <tr>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Due</th>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Channel</th>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Note</th>

                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                @forelse ($followUps as $followUp)

                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">

                                        <td class="px-3 py-2 text-sm text-gray-800 dark:text-gray-100">

                                            {{ $followUp->due_date?->format('Y-m-d') }}

                                            @if (! $followUp->is_done && $followUp->due_date && $followUp->due_date->isPast())

                                                <span class="ml-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Overdue</span>

                                            @endif

                                        </td>

                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $channelLabels[$followUp->channel] ?? '-' }}</td>

                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $followUp->creator?->name ? 'by ' . $followUp->creator->name : 'by -' }}
                                            </div>
                                            <div class="text-sm text-gray-700 dark:text-gray-200">
                                                {{ $followUp->note ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="px-3 py-2 text-right text-sm">

                                            @if ($followUp->is_done)

                                                <div class="inline-flex items-center gap-2">
                                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Done</span>
                                                    @if (! empty($followUp->done_reason))
                                                        <button type="button" class="btn-outline-sm" aria-label="View done reason"
                                                            x-data x-on:click.prevent="$dispatch('open-modal', 'followup-reason-{{ $followUp->id }}')">
                                                            <i class="fas fa-comment-dots"></i>
                                                        </button>
                                                    @endif
                                                </div>

                                            @else

                                                @if ($canMarkFollowUpDone && ! $inquiry->isFinal())

                                                    <button type="button" class="btn-primary-sm" x-data
                                                        x-on:click.prevent="$dispatch('open-modal', 'followup-done-{{ $followUp->id }}')">
                                                        Mark Done
                                                    </button>

                                                @else

                                                    <span class="text-xs text-gray-400">Locked</span>

                                                @endif

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No reminders yet.</td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>
                    <div><p class="text-sm text-gray-600 dark:text-gray-300">Fill on the input form to create remider!</p></div>
                    @if ($canMarkFollowUpDone && ! $inquiry->isFinal())
                        @foreach ($followUps->where('is_done', false) as $followUp)
                            <x-modal name="followup-done-{{ $followUp->id }}" focusable>
                                <form method="POST" action="{{ route('inquiries.followups.done', $followUp) }}" class="p-6 space-y-4">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Mark Follow-up Done</h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">Please provide the reason for closing this reminder.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Reason</label>
                                        <textarea name="done_reason" rows="3" class="mt-1 w-full app-input" required></textarea>
                                        @error('done_reason')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">Cancel</button>
                                        <button class="btn-primary">Mark Done</button>
                                    </div>
                                </form>
                            </x-modal>
                        @endforeach
                    @endif
                    @foreach ($followUps->where('is_done', true) as $followUp)
                        @if (! empty($followUp->done_reason))
                            <x-modal name="followup-reason-{{ $followUp->id }}" focusable>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Reminder Reason</h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">Reason submitted when marking done.</p>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Reminder Note</div>
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                                {!! $followUp->note ?: '-' !!}
                                            </div>
                                        </div>
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Done Reason</div>
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                                {!! $followUp->done_reason !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">Close</button>
                                    </div>
                                </div>
                            </x-modal>
                        @endif
                    @endforeach

                    @if ($canManageFollowUp && ! $inquiry->isFinal())

                        <form method="POST" action="{{ route('inquiries.followups.store', $inquiry) }}" class="space-y-3 pb-6">

                            @csrf

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Due Date</label>

                                <input name="due_date" type="date" class="mt-1 app-input" required>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Channel</label>

                                <select name="channel" class="mt-1 app-input">

                                    <option value="">-</option>

                                    @foreach (($channelLabels ?? []) as $value => $label)

                                        <option value="{{ $value }}">{{ $label }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Note</label>

                                <input name="note" type="text" class="mt-1 app-input">

                            </div>

                            <button  class="btn-primary">

                                Add Reminder

                            </button>

                        </form>
                    @endif


                    
                    

                </div>



                <div class="app-card p-6 space-y-4">

                    <div>

                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Communication History</h2>

                        <p class="text-sm text-gray-600 dark:text-gray-300">Log communications with customers.</p>

                    </div>



                    @if ($canManageInquiry && ! $inquiry->isFinal())

                        <form method="POST" action="{{ route('inquiries.communications.store', $inquiry) }}" class="space-y-3">

                            @csrf

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Channel</label>

                                <select name="channel" class="mt-1 app-input" required>

                                    @foreach (($channelLabels ?? []) as $value => $label)

                                        <option value="{{ $value }}">{{ $label }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact At</label>

                                <input name="contact_at" type="datetime-local" class="mt-1 app-input">

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Summary</label>

                                <input name="summary" type="text" class="mt-1 app-input" required>

                            </div>

                            <button  class="btn-primary">

                                Add History

                            </button>

                        </form>
                    @endif



                    <div class="relative">

                        <div class="absolute left-3 top-0 h-full w-px bg-gray-200 dark:bg-gray-700"></div>

                        <div class="space-y-6">

                            @forelse ($communications as $item)

                                <div class="relative pl-8">

                                    <div class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-indigo-600"></div>

                                    <div class="rounded-lg mb-6 border border-gray-200 p-4 text-sm dark:border-gray-700">

                                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">

                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">{{ $channelLabels[$item->channel] ?? '-' }}</span>

                                            <span>{{ $item->contact_at?->format('Y-m-d H:i') ?? '-' }}</span>

                                            <span>by {{ $item->creator->name ?? '-' }}</span>

                                        </div>

                                        <p class="mt-2 text-sm text-gray-800 dark:text-gray-100">{{ $item->summary }}</p>

                                    </div>

                                </div>

                            @empty

                                <div class="pl-8 text-sm text-gray-500 dark:text-gray-400">No history yet.</div>

                            @endforelse

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
















