@extends('layouts.master')

@section('content')
    <div class="max-w-6xl space-y-6" x-data="{ openReminder: false, openHistory: false }">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Inquiry</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update inquiry {{ $inquiry->inquiry_number }}.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form method="POST" action="{{ route('sales.inquiries.update', $inquiry) }}">
                    @csrf
                    @method('PUT')
                    @include('sales.inquiries._form', [
                        'inquiry' => $inquiry,
                        'buttonLabel' => 'Update Inquiry',
                    ])
                </form>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Reminder Follow-up</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Set the next follow-up schedule.</p>
                    </div>
                    <button type="button" class="lg:hidden text-xs font-medium text-indigo-600 hover:text-indigo-700"
                            @click="openReminder = !openReminder"
                            x-text="openReminder ? 'Hide' : 'Show'"></button>
                </div>

                <div x-show="openReminder" x-transition>
                <form method="POST" action="{{ route('sales.inquiries.followups.store', $inquiry) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Due Date</label>
                            <input name="due_date" type="date" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Channel</label>
                            <select name="channel" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">-</option>
                                @foreach (['phone' => 'Phone', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'meeting' => 'Meeting', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Note</label>
                            <input name="note" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Add Reminder
                    </button>
                </form>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
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
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $followUp->channel ?? '-' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">{{ $followUp->note ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right text-sm">
                                        @if ($followUp->is_done)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Done</span>
                                        @else
                                            <form method="POST" action="{{ route('sales.inquiries.followups.done', $followUp) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded-lg bg-gray-900 px-3 py-1 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">
                                                    Mark Done
                                                </button>
                                            </form>
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
                </div>
            </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Communication History</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Log communications with customers.</p>
                </div>
                <div class="flex items-center justify-end">
                    <button type="button" class="lg:hidden text-xs font-medium text-indigo-600 hover:text-indigo-700"
                            @click="openHistory = !openHistory"
                            x-text="openHistory ? 'Hide' : 'Show'"></button>
                </div>

                <div x-show="openHistory" x-transition>
                <form method="POST" action="{{ route('sales.inquiries.communications.store', $inquiry) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Channel</label>
                            <select name="channel" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                                @foreach (['phone' => 'Phone', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'meeting' => 'Meeting', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact At</label>
                            <input name="contact_at" type="datetime-local" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Summary</label>
                            <input name="summary" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                    </div>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Add History
                    </button>
                </form>

                <div class="relative">
                    <div class="absolute left-3 top-0 h-full w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="space-y-6">
                        @forelse ($communications as $item)
                            <div class="relative pl-8">
                                <div class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-indigo-600"></div>
                                <div class="rounded-lg border border-gray-200 p-4 text-sm dark:border-gray-700">
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">{{ $item->channel }}</span>
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
    </div>
@endsection
