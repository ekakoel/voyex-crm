@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        @section('page_actions')<a href="{{ route('currencies.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add Currency</a>@endsection

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if ($errors->has('currency'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ $errors->first('currency') }}</div>
        @endif

        <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 md:grid-cols-3">
            <input name="q" value="{{ request('q') }}" placeholder="Search code/name" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <div class="md:col-span-2 flex items-center gap-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('currencies.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Reset</a>
            </div>
        </form>

        @if (auth()->user()?->hasAnyRole(['Sales Manager', 'Admin', 'Super Admin']))
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Bulk Update Rates</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Update up to 10 currencies in one submit.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('currencies.bulk-update') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach (($bulkCurrencies ?? collect()) as $index => $currency)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $currency->code }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $currency->name }}</div>
                                </div>
                                <input type="hidden" name="rates[{{ $index }}][id]" value="{{ $currency->id }}">
                                <div class="mt-2">
                                    <x-money-input
                                        label="Rate to IDR"
                                        name="rates[{{ $index }}][rate_to_idr]"
                                        :value="old('rates.' . $index . '.rate_to_idr', $currency->rate_to_idr)"
                                        min="0"
                                        step="0.000001"
                                        badge="IDR"
                                        compact
                                    />
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs text-gray-500">Decimals</label>
                                    <input
                                        name="rates[{{ $index }}][decimal_places]"
                                        type="number"
                                        min="0"
                                        max="6"
                                        value="{{ old('rates.' . $index . '.decimal_places', $currency->decimal_places) }}"
                                        class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save Rates</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Symbol</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Rate to IDR</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Decimals</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($currencies as $currency)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $currency->code }}
                                @if ($currency->is_default)
                                    <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Default</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $currency->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $currency->symbol ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">{{ number_format((float) $currency->rate_to_idr, 6, '.', ',') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">{{ $currency->decimal_places }}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if ($currency->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-700/60 dark:text-gray-300">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('currencies.edit', $currency) }}" class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                                <form action="{{ route('currencies.destroy', $currency) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this currency?')" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No currency data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $currencies->links() }}</div>
    </div>
@endsection




