@extends('layouts.master')



@section('page_title', ui_phrase('Inquiry Detail'))

@section('page_subtitle', ui_phrase('Review complete inquiry information.'))

@section('page_actions')

    @can('update', $inquiry)

        @if (!in_array(($inquiry->quotation->status ?? ''), ['approved', \App\Models\Quotation::FINAL_STATUS], true) && ! $inquiry->isFinal())

            <a href="{{ route('inquiries.edit', $inquiry) }}"  class="btn-secondary">

                {{ ui_phrase('Edit Inquiry') }}
            </a>
        @endif

    @endcan

@endsection



@section('content')

    <div class="max-w-6xl module-page module-page--inquiries">

        @if (session('success'))

            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">

                {{ session('success') }}

            </div>

        @endif

        @if (session('error'))

            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">

                {{ session('error') }}

            </div>

        @endif



        @if ($errors->any())

            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">

                {{ $errors->first() }}

            </div>

        @endif



        <div class="module-grid-8-4">

            <div class="module-grid-side lg:order-2">
                <div class="app-card p-5">

                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Inquiry Overview') }}</h2>

                    <dl class="app-dl" class="mt-4 space-y-3 text-sm">

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer:') }}</dt>

                        <dd class="text-right font-medium text-gray-800 dark:text-gray-100">({{ $inquiry->customer->code ?? '-' }}) {{ $inquiry->customer->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}</dt>

                        <dd><x-status-badge :status="$inquiry->status" size="xs" /></dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Priority') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ ui_phrase((string) $inquiry->priority) }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Source') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->source ? ui_phrase((string) $inquiry->source) : '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Assigned to:') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->assignedUser->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Deadline') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->deadline?->format('Y-m-d') ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Reminder Email') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->reminder_enabled ? ui_phrase('Enabled') : ui_phrase('Disabled') }}</dd>

                    </div>

                    <div>

                        <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Notes') }}:</div>

                        <dd class="text-left font-medium text-gray-800 dark:text-gray-100">{!! $inquiry->notes ?: '-' !!}</dd>

                    </div>

                    </dl>

                </div>

                <div class="app-card p-5">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('related records') }}</h2>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('related itineraries') }}</h3>
                            <div class="mt-2 space-y-2">
                                @forelse (($itineraries ?? collect()) as $itinerary)
                                    <a href="{{ route('itineraries.show', $itinerary) }}" class="block rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900/30">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $itinerary->title ?: '-' }}</p>
                                            <x-status-badge :status="$itinerary->status" size="xs" />
                                        </div>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('no related itineraries') }}</p>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('related quotations') }}</h3>
                            <div class="mt-2 space-y-2">
                                @forelse (($quotations ?? collect()) as $quotation)
                                    <a href="{{ route('quotations.show', $quotation) }}" class="block rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900/30">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->quotation_number ?: '-' }}</p>
                                            <x-status-badge :status="$quotation->status" size="xs" />
                                        </div>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('no related quotations') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Activity Timeline') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('tracking changes') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
            </div>



            <div class="module-grid-main lg:order-1">

                <div class="app-card p-5">

                    <div class="flex mb-2 items-start justify-between gap-2">

                        <div>

                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Reminder Follow-up') }}</h2>

                        </div>

                    </div>

                    <div class="overflow-x-auto app-card">

                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">

                            <thead>

                                <tr>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Due Date') }}</th>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Channel') }}</th>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Note') }}</th>

                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Status') }}</th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                @forelse ($followUps as $followUp)

                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">

                                        <td class="px-3 py-2 text-sm text-gray-800 dark:text-gray-100">

                                            {{ $followUp->due_date?->format('Y-m-d') }}

                                            @if (! $followUp->is_done && $followUp->due_date && $followUp->due_date->isPast())

                                                <span class="ml-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ ui_phrase('Overdue') }}</span>

                                            @endif

                                        </td>

                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $followUp->channel ? ui_phrase((string) $followUp->channel) : '-' }}</td>

                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $followUp->creator?->name ? ui_phrase('by label', ['name' => $followUp->creator->displayNameFor(auth()->user(), ui_phrase('system'))]) : ui_phrase('by fallback') }}
                                            </div>
                                            <div class="text-sm text-gray-700 dark:text-gray-200">
                                                {{ $followUp->note ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="px-3 py-2 text-right text-sm">

                                            @if ($followUp->is_done)

                                                <div class="inline-flex items-center gap-2">
                                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ ui_phrase('Done') }}</span>
                                                    @if (! empty($followUp->done_reason))
                                                        <button type="button" class="btn-outline-sm" aria-label="{{ ui_phrase('View done reason') }}"
                                                            x-data x-on:click.prevent="$dispatch('open-modal', 'followup-reason-{{ $followUp->id }}')">
                                                            <i class="fas fa-comment-dots"></i>
                                                        </button>
                                                    @endif
                                                </div>

                                            @else

                                                @if ($canMarkFollowUpDone && ! $inquiry->isFinal())

                                                    <button type="button" class="btn-primary-sm" x-data
                                                        x-on:click.prevent="$dispatch('open-modal', 'followup-done-{{ $followUp->id }}')">
                                                        {{ ui_phrase('Mark Done') }}
                                                    </button>

                                                @else

                                                    <span class="text-xs text-gray-400">{{ ui_phrase('Locked') }}</span>

                                                @endif

                                            @endif

                                            @if (($canResetFollowUpReminder ?? false) && ! is_null($followUp->last_reminded_at))
                                                <div class="mt-2 flex items-center justify-end">
                                                    <form method="POST" action="{{ route('inquiries.followups.reset-reminder', $followUp) }}" onsubmit="return confirm('{{ ui_phrase('confirm reset reminder') }}')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn-outline-sm">{{ ui_phrase('Reset Reminder') }}</button>
                                                    </form>
                                                </div>
                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No reminders yet.') }}</td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>
                    <div><p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('fill input reminder') }}</p></div>
                    @if ($canMarkFollowUpDone && ! $inquiry->isFinal())
                        @foreach ($followUps->where('is_done', false) as $followUp)
                            <x-modal name="followup-done-{{ $followUp->id }}" focusable>
                                <form method="POST" action="{{ route('inquiries.followups.done', $followUp) }}" class="p-6 space-y-4">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Mark Follow-up Done') }}</h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('provide reason close') }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Reason') }}</label>
                                        <textarea name="done_reason" rows="3" class="mt-1 w-full app-input" required></textarea>
                                        @error('done_reason')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ ui_phrase('Cancel') }}</button>
                                        <button class="btn-primary">{{ ui_phrase('Mark Done') }}</button>
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
                                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Reminder Reason') }}</h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('reason submitted') }}</p>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Reminder Note') }}</div>
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                                {!! $followUp->note ?: '-' !!}
                                            </div>
                                        </div>
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Reason Note') }}</div>
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                                {!! $followUp->done_reason !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ ui_phrase('Close') }}</button>
                                    </div>
                                </div>
                            </x-modal>
                        @endif
                    @endforeach

                    @if ($canManageFollowUp && ! $inquiry->isFinal())

                        <form method="POST" action="{{ route('inquiries.followups.store', $inquiry) }}" class="space-y-3 pb-6">

                            @csrf

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Due Date') }}</label>

                                <input name="due_date" type="date" class="mt-1 app-input" required>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Channel') }}</label>

                                <select name="channel" class="mt-1 app-input">

                                    <option value="">-</option>

                                    @foreach (($channelLabels ?? []) as $value => $label)

                                        <option value="{{ $value }}">{{ ui_phrase((string) $value) }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Note') }}</label>

                                <input name="note" type="text" class="mt-1 app-input">

                            </div>

                            <button  class="btn-primary">

                                {{ ui_phrase('Add Reminder') }}

                            </button>

                        </form>
                    @endif


                    
                    

                </div>



                <div class="app-card p-5">

                    <div>

                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Communication History') }}</h2>

                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('log communications') }}</p>

                    </div>



                    <div class="relative mt-3">
                        @forelse ($communications as $item)
                            <div class="relative mb-3">
                                <div class="absolute left-1 top-1.5 h-3 w-3 rounded-full bg-indigo-600"></div>
                                <div class="rounded-lg border border-gray-200 p-4 text-sm dark:border-gray-700 bg-gray-100">
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">{{ $item->channel ? ui_phrase((string) $item->channel) : '-' }}</span>
                                        <span><x-local-time :value="$item->contact_at" /></span>
                                        <span>{{ ui_phrase('by label', ['name' => $item->creator->name ?? '-']) }}</span>
                                    </div>
                                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $item->summary }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No history yet.') }}</div>
                        @endforelse
                    </div>

                    @if ($canManageCommunication ?? false)

                        <form method="POST" action="{{ route('inquiries.communications.store', $inquiry) }}" class="space-y-3">

                            @csrf

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Channel') }}</label>

                                <select name="channel" class="mt-1 app-input" required>

                                    @foreach (($channelLabels ?? []) as $value => $label)

                                        <option value="{{ $value }}">{{ ui_phrase((string) $value) }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Contact At') }}</label>

                                <input name="contact_at" type="datetime-local" class="mt-1 app-input">

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Summary') }}</label>

                                <input name="summary" type="text" class="mt-1 app-input" required>

                            </div>

                            <button  class="btn-primary">

                                {{ ui_phrase('Add History') }}

                            </button>

                        </form>
                    @endif

                </div>

            </div>

        </div>

    </div>

@endsection













