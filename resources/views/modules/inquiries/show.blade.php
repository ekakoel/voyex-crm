@extends('layouts.master')



@section('page_title', __('ui.modules.inquiries.show_page_title'))

@section('page_subtitle', __('ui.modules.inquiries.show_page_subtitle'))

@section('page_actions')

    @can('update', $inquiry)

        @if (!($inquiry->quotation && ($inquiry->quotation->status ?? '') === 'approved') && ! $inquiry->isFinal())

            <a href="{{ route('inquiries.edit', $inquiry) }}"  class="btn-secondary">

                {{ __('ui.modules.inquiries.edit_page_title') }}
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



        <div class="module-grid-8-4">

            <div class="module-grid-side lg:order-2 space-y-6">
                <div class="app-card p-5 mb-6">

                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.inquiries.inquiry_overview') }}</h2>

                    <dl class="app-dl" class="mt-4 space-y-3 text-sm">

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.customer') }}</dt>

                        <dd class="text-right font-medium text-gray-800 dark:text-gray-100">({{ $inquiry->customer->code ?? '-' }}) {{ $inquiry->customer->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}</dt>

                        <dd><x-status-badge :status="$inquiry->status" size="xs" /></dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.priority') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->priority }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.source') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $sourceLabels[$inquiry->source] ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.assigned_to') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->assignedUser->name ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.common.deadline') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->deadline?->format('Y-m-d') ?? '-' }}</dd>

                    </div>

                    <div class="flex items-start items-center justify-between gap-3">

                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.reminder_email') }}</dt>

                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ $inquiry->reminder_enabled ? __('ui.common.enabled') : __('ui.common.disabled') }}</dd>

                    </div>

                    <div>

                        <div class="text-gray-500 dark:text-gray-400">{{ __('ui.common.notes') }}:</div>

                        <dd class="text-left font-medium text-gray-800 dark:text-gray-100">{!! $inquiry->notes ?: '-' !!}</dd>

                    </div>

                    </dl>

                </div>

                <div class="app-card p-6 space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.activity_timeline') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('ui.modules.inquiries.tracking_changes') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
            </div>



            <div class="module-grid-main lg:order-1 space-y-6">

                <div class="app-card p-6 space-y-4 mb-6">

                    <div class="flex mb-2 items-start justify-between gap-2">

                        <div>

                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.reminder_follow_up') }}</h2>

                        </div>

                    </div>

                    <div class="overflow-x-auto app-card">

                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">

                            <thead>

                                <tr>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.inquiries.due_date') }}</th>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.channel') }}</th>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.inquiries.note') }}</th>

                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.status') }}</th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                @forelse ($followUps as $followUp)

                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">

                                        <td class="px-3 py-2 text-sm text-gray-800 dark:text-gray-100">

                                            {{ $followUp->due_date?->format('Y-m-d') }}

                                            @if (! $followUp->is_done && $followUp->due_date && $followUp->due_date->isPast())

                                                <span class="ml-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ __('ui.common.overdue') }}</span>

                                            @endif

                                        </td>

                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $channelLabels[$followUp->channel] ?? '-' }}</td>

                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $followUp->creator?->name ? __('ui.modules.inquiries.by_label', ['name' => $followUp->creator->name]) : __('ui.modules.inquiries.by_fallback') }}
                                            </div>
                                            <div class="text-sm text-gray-700 dark:text-gray-200">
                                                {{ $followUp->note ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="px-3 py-2 text-right text-sm">

                                            @if ($followUp->is_done)

                                                <div class="inline-flex items-center gap-2">
                                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('ui.common.done') }}</span>
                                                    @if (! empty($followUp->done_reason))
                                                        <button type="button" class="btn-outline-sm" aria-label="{{ __('ui.modules.inquiries.view_done_reason') }}"
                                                            x-data x-on:click.prevent="$dispatch('open-modal', 'followup-reason-{{ $followUp->id }}')">
                                                            <i class="fas fa-comment-dots"></i>
                                                        </button>
                                                    @endif
                                                </div>

                                            @else

                                                @if ($canMarkFollowUpDone && ! $inquiry->isFinal())

                                                    <button type="button" class="btn-primary-sm" x-data
                                                        x-on:click.prevent="$dispatch('open-modal', 'followup-done-{{ $followUp->id }}')">
                                                        {{ __('ui.common.mark_done') }}
                                                    </button>

                                                @else

                                                    <span class="text-xs text-gray-400">{{ __('ui.common.locked') }}</span>

                                                @endif

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.no_reminders_yet') }}</td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>
                    <div><p class="text-sm text-gray-600 dark:text-gray-300">{{ __('ui.modules.inquiries.fill_input_reminder') }}</p></div>
                    @if ($canMarkFollowUpDone && ! $inquiry->isFinal())
                        @foreach ($followUps->where('is_done', false) as $followUp)
                            <x-modal name="followup-done-{{ $followUp->id }}" focusable>
                                <form method="POST" action="{{ route('inquiries.followups.done', $followUp) }}" class="p-6 space-y-4">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.inquiries.mark_follow_up_done') }}</h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('ui.modules.inquiries.provide_reason_close') }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.common.reason') }}</label>
                                        <textarea name="done_reason" rows="3" class="mt-1 w-full app-input" required></textarea>
                                        @error('done_reason')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('ui.common.cancel') }}</button>
                                        <button class="btn-primary">{{ __('ui.common.mark_done') }}</button>
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
                                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.inquiries.reminder_reason') }}</h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('ui.modules.inquiries.reason_submitted') }}</p>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.reminder_note') }}</div>
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                                {!! $followUp->note ?: '-' !!}
                                            </div>
                                        </div>
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.reason_note') }}</div>
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                                {!! $followUp->done_reason !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('ui.common.close') }}</button>
                                    </div>
                                </div>
                            </x-modal>
                        @endif
                    @endforeach

                    @if ($canManageFollowUp && ! $inquiry->isFinal())

                        <form method="POST" action="{{ route('inquiries.followups.store', $inquiry) }}" class="space-y-3 pb-6">

                            @csrf

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.inquiries.due_date') }}</label>

                                <input name="due_date" type="date" class="mt-1 app-input" required>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.common.channel') }}</label>

                                <select name="channel" class="mt-1 app-input">

                                    <option value="">-</option>

                                    @foreach (($channelLabels ?? []) as $value => $label)

                                        <option value="{{ $value }}">{{ $label }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.inquiries.note') }}</label>

                                <input name="note" type="text" class="mt-1 app-input">

                            </div>

                            <button  class="btn-primary">

                                {{ __('ui.common.add_reminder') }}

                            </button>

                        </form>
                    @endif


                    
                    

                </div>



                <div class="app-card p-6 space-y-4">

                    <div>

                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.communication_history') }}</h2>

                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('ui.modules.inquiries.log_communications') }}</p>

                    </div>



                    @if ($canManageInquiry && ! $inquiry->isFinal())

                        <form method="POST" action="{{ route('inquiries.communications.store', $inquiry) }}" class="space-y-3">

                            @csrf

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.common.channel') }}</label>

                                <select name="channel" class="mt-1 app-input" required>

                                    @foreach (($channelLabels ?? []) as $value => $label)

                                        <option value="{{ $value }}">{{ $label }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.common.contact_at') }}</label>

                                <input name="contact_at" type="datetime-local" class="mt-1 app-input">

                            </div>

                            <div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.common.summary') }}</label>

                                <input name="summary" type="text" class="mt-1 app-input" required>

                            </div>

                            <button  class="btn-primary">

                                {{ __('ui.common.add_history') }}

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

                                            <span><x-local-time :value="$item->contact_at" /></span>

                                            <span>{{ __('ui.modules.inquiries.by_label', ['name' => $item->creator->name ?? '-']) }}</span>

                                        </div>

                                        <p class="mt-2 text-sm text-gray-800 dark:text-gray-100">{{ $item->summary }}</p>

                                    </div>

                                </div>

                            @empty

                                <div class="pl-8 text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.inquiries.no_history_yet') }}</div>

                            @endforelse

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection




















