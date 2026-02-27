@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Transport Services</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Manage transport partners and contract rates per unit.</p>
            </div>
            <a href="{{ route('transports.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add Transport</a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 md:grid-cols-4">
            <input name="q" value="{{ request('q') }}" placeholder="Search code/name/provider/city" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <select name="transport_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">All Types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected((string) request('transport_type') === (string) $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <div class="md:col-span-2 flex items-center gap-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('transports.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Units</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Min Contract Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($transports as $transport)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $transport->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $transport->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $transport->provider_name ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $transport->transport_type)) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($transport->city ?? '') . (($transport->city && $transport->province) ? ', ' : '') . ($transport->province ?? '')) ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $transport->units_count }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $transport->units_min_contract_rate !== null ? number_format((float) $transport->units_min_contract_rate, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('transports.show', $transport) }}" class="mr-3 font-medium text-slate-600 hover:text-slate-700 dark:text-slate-300">View</a>
                                <a href="{{ route('transports.edit', $transport) }}" class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                                <form action="{{ route('transports.destroy', $transport) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this transport service?')" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No transport services available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $transports->links() }}</div>
    </div>
@endsection
