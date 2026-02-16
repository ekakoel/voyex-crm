@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Inquiries</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Manage customer inquiries.</p>
            </div>
            <a href="{{ route('sales.inquiries.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Add Inquiry
            </a>
        </div>

        <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:grid-cols-6">
            <input name="q" value="{{ request('q') }}" placeholder="Search number / customer" class="md:col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Status</option>
                @foreach (['new','follow_up','quoted','converted','closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="priority" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Priority</option>
                @foreach (['low','normal','high'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>
                @endforeach
            </select>
            <select name="source" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Source</option>
                @foreach (['phone' => 'Phone', 'email' => 'Email', 'website' => 'Website', 'walk-in' => 'Walk-in', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="customer_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Customer</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) request('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
            <select name="assigned_to" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Assigned</option>
                @foreach ($assignees as $user)
                    <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input name="deadline_from" type="date" value="{{ request('deadline_from') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <input name="deadline_to" type="date" value="{{ request('deadline_to') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <select name="per_page" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                @foreach ([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2 md:col-span-6">
                <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Filter</button>
                <a href="{{ route('sales.inquiries.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Reset</a>
            </div>
        </form>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="md:hidden space-y-3">
            @forelse ($inquiries as $inquiry)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $inquiry->inquiry_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $inquiry->customer->name ?? '-' }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $inquiry->status }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>Priority</div><div>{{ $inquiry->priority }}</div>
                        <div>Assigned</div><div>{{ $inquiry->assignedUser->name ?? '-' }}</div>
                        <div>Deadline</div><div>{{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('sales.inquiries.edit', $inquiry) }}" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Edit</a>
                        <form action="{{ route('sales.inquiries.destroy', $inquiry) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this inquiry?')" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/20">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    No inquiries available.
                </div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Assigned</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Deadline</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($inquiries as $inquiry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $inquiry->inquiry_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $inquiry->customer->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $inquiry->status }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $inquiry->priority }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $inquiry->assignedUser->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('sales.inquiries.edit', $inquiry) }}" class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                                <form action="{{ route('sales.inquiries.destroy', $inquiry) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this inquiry?')" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No inquiries available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $inquiries->links() }}</div>
    </div>
@endsection
