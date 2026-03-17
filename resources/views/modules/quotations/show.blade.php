@extends('layouts.master')



@section('content')

    @php

        $subTotal = (float) ($quotation->sub_total ?? 0);

        $discountType = $quotation->discount_type ?? null;

        $discountValue = (float) ($quotation->discount_value ?? 0);

        $discountAmount = 0;

        if ($discountType === 'percent') {

            $discountAmount = $subTotal * ($discountValue / 100);

        } elseif ($discountType === 'fixed') {

            $discountAmount = $discountValue;

        }

    @endphp

        <div class="space-y-6 module-page module-page--quotations">

            @section('page_actions')@can('update', $quotation)

                @if (! in_array(($quotation->status ?? ''), ['approved', 'final'], true))

                    <a href="{{ route('quotations.edit', $quotation) }}"  class="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/20">

                        Edit

                    </a>

                @endif

            @endcan

                <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener"  class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">

                    PDF

                </a>

                <a href="{{ route('quotations.index') }}"  class="btn-ghost">Back</a>@endsection

        </div>



        <div class="grid gap-6 xl:grid-cols-12">

            <div class="xl:col-span-8 space-y-4">

                <div class="app-card p-6">

                    <dl class="app-dl" class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Quotation Number</dt>

                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->quotation_number }}</dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Status</dt>

                            <dd class="text-sm"><x-status-badge :status="$quotation->status" size="xs" /></dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Validity Date</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100">{{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Itinerary</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100">#{{ $quotation->itinerary?->id ?? '-' }} - {{ $quotation->itinerary?->title ?? '-' }}</dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Destination</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100">{{ $quotation->itinerary?->destination ?? '-' }}</dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Booking</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100">{{ $quotation->booking?->booking_number ?? '-' }}</dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Final Amount</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$quotation->final_amount ?? 0" currency="IDR" /></dd>

                        </div>

                    </dl>

                </div>



                <div class="module-card p-6">

                    <div class="flex items-center justify-between gap-3">

                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Items</h2>

                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $quotation->items->count() }} item</span>

                    </div>

                    <div class="mt-4 overflow-x-auto app-card">

                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">

                            <thead>

                                <tr>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Description</th>

                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Qty</th>

                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Unit Price</th>

                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Discount Type</th>

                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Discount</th>

                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                @forelse ($quotation->items as $item)

                                    <tr>

                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">{{ $item->description }}</td>

                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">{{ $item->qty }}</td>

                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200"><x-money :amount="$item->unit_price ?? 0" currency="IDR" /></td>

                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $item->discount_type === 'percent' ? 'Percent' : 'Fixed' }}</td>

                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">

                                            @if (($item->discount_type ?? 'fixed') === 'percent')

                                                {{ number_format($item->discount ?? 0, 2, ',', '.') }}%

                                            @else

                                                <x-money :amount="$item->discount ?? 0" currency="IDR" />

                                            @endif

                                        </td>

                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200"><x-money :amount="$item->total ?? 0" currency="IDR" /></td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No items available.</td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>



                <div class="module-card p-6">

                    <dl class="app-dl" class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Sub Total</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$subTotal" currency="IDR" /></dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Discount</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100">

                                @if ($discountType === 'percent')

                                    Percent ({{ number_format($discountValue, 2, ',', '.') }}%)

                                @elseif ($discountType === 'fixed')

                                    Fixed (<x-money :amount="$discountValue" currency="IDR" />)

                                @else

                                    -

                                @endif

                            </dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Discount Amount</dt>

                            <dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$discountAmount" currency="IDR" /></dd>

                        </div>

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">Final Amount</dt>

                            <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$quotation->final_amount ?? 0" currency="IDR" /></dd>

                        </div>

                    </dl>

                </div>

            </div>



            <aside  class="xl:col-span-4 space-y-4">

                @if ($quotation->itinerary?->inquiry)

                    <div class="module-card p-6 space-y-3">

                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Inquiry Detail</p>

                        <dl class="app-dl" class="space-y-1 text-xs text-gray-700 dark:text-gray-200">

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

                        </dl>

                        @if ($quotation->itinerary)

                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Itinerary Info</p>

                            <dl class="app-dl" class="space-y-1 text-xs text-gray-700 dark:text-gray-200">

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

                        @endif

                    </div>

                @endif

                @include('partials._quotation-comments', ['quotation' => $quotation])

                <div class="module-card p-6 space-y-3">

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Validation</p>

                    <dl class="app-dl" class="space-y-2 text-xs text-gray-700 dark:text-gray-200">

                        <div class="flex justify-between gap-3">

                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>

                            <dd class="font-medium text-right">{{ $quotation->status ?? '-' }}</dd>

                        </div>

                        @if (($quotation->status ?? '') === 'approved')

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Approved by</dt>

                                <dd class="font-medium text-right">{{ $quotation->approvedBy?->name ?? '-' }}</dd>

                            </div>

                            <div class="flex justify-between gap-3">

                                <dt class="text-gray-500 dark:text-gray-400">Approved at</dt>

                                <dd class="font-medium text-right">{{ $quotation->approved_at?->format('Y-m-d H:i') ?? '-' }}</dd>

                            </div>

                        @endif

                    </dl>



                    @if (!empty($quotation->approval_note))

                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">

                            <div class="font-semibold">Validation Note</div>

                            <p class="mt-1">{{ $quotation->approval_note }}</p>

                        </div>

                    @endif

                    @if (auth()->user()?->hasAnyRole(['Director']) && ($quotation->status ?? '') !== 'final')

                        <form method="POST" action="{{ route('quotations.approve', $quotation) }}" class="pt-3 space-y-3">

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



                



                @include('partials._audit-info', ['record' => $quotation, 'title' => 'Audit Info'])

            </aside>

        </div>

    </div>

@endsection







