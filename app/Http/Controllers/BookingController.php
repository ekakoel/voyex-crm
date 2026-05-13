<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\QuotationItem;
use App\Models\Quotation;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\IslandTransfer;
use App\Models\TransportUnit;
use App\Models\HotelRoom;
use App\Support\CompanySettingsCache;
use App\Services\BookingVoucherService;
use App\Services\InvoiceService;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly BookingVoucherService $voucherService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Booking::query()
            ->withCount([
                'items',
                'items as items_with_voucher_count' => fn ($itemQuery) => $itemQuery->whereHas('voucher'),
            ])
            ->with([
                'quotation' => function ($quotationQuery) {
                    $quotationQuery
                        ->withCount('items')
                        ->with('inquiry.customer');
                },
            ]);

        $this->applyBookingIndexFilters($query);

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $sidebarQuery = Booking::query();
        $this->applyBookingIndexFilters($sidebarQuery);
        $sidebarInfo = $this->buildBookingSidebarInfo($sidebarQuery);
        $bookings = $query->latest()->paginate($perPage)->withQueryString();
        $quotations = Quotation::query()
            ->with('inquiry.customer')
            ->whereHas('booking')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('modules.bookings.index', compact('bookings', 'quotations', 'sidebarInfo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $quotations = $this->eligibleQuotationQuery()
            ->get();

        return view('modules.bookings.create', compact('quotations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        unset($validated['items']);
        $validated['status'] = $this->resolveAutoStatus((string) ($validated['travel_date'] ?? ''));
        $validated['booking_number'] = $this->generateBookingNumber();

        $booking = Booking::query()->create($validated);
        $this->syncBookingItems($booking, $items);
        $this->invoiceService->generateForBooking($booking);

        return redirect()
            ->route('bookings.index')
            ->with('success', ui_phrase('Booking created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        $booking->load([
            'quotation.inquiry.customer',
            'quotation.inquiry.assignedUser',
            'quotation.itinerary.destination',
            'quotation.items',
            'items.serviceable',
            'items.voucher',
            'items.bookingLogs.creator',
            'items.latestBookingLog.creator',
        ]);
        $booking->quotation?->items?->loadMorph('serviceable', [
            Activity::class => ['vendor'],
            FoodBeverage::class => ['vendor'],
            IslandTransfer::class => ['vendor'],
            TransportUnit::class => ['vendor'],
            HotelRoom::class => ['hotel'],
        ]);
        $sourceUpdatedMap = [];
        $voucherService = app(\App\Services\BookingVoucherService::class);
        foreach ($booking->items as $item) {
            $sourceUpdatedMap[$item->id] = $voucherService->isSourceUpdated($item);
        }

        $company = CompanySettingsCache::get();
        $companyName = trim((string) ($company?->company_name ?: 'BALI KAMI TOURS'));
        $companyAddress = collect([
            $company?->address ?? null,
            $company?->city ?? null,
            $company?->province ?? null,
        ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');
        $companyEmail = trim((string) ($company?->contact_email ?? ''));

        return view('modules.bookings.show', compact(
            'booking',
            'sourceUpdatedMap',
            'companyName',
            'companyAddress',
            'companyEmail'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        if (! $this->canManageBooking($booking, 'update')) {
            return $this->denyBookingMutation($booking);
        }
        if ($booking->isFinal()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking is final and cannot be edited.'));
        }
        $quotations = $this->eligibleQuotationQuery($booking->id)
            ->get();
        $booking->load('items');

        return view('modules.bookings.edit', compact('booking', 'quotations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        if (! $this->canManageBooking($booking, 'update')) {
            return $this->denyBookingMutation($booking);
        }
        if ($booking->isFinal()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking is final and cannot be edited.'));
        }
        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        unset($validated['items']);
        $validated['status'] = $this->resolveAutoStatus((string) ($validated['travel_date'] ?? ''), (string) $booking->status);
        $booking->update($validated);
        $this->syncBookingItems($booking, $items);
        $this->invoiceService->generateForBooking($booking);

        return redirect()
            ->route('bookings.index')
            ->with('success', ui_phrase('Booking updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        if (! $this->canManageBooking($booking, 'delete')) {
            return $this->denyBookingMutation($booking);
        }
        if ($booking->isFinal()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking is final and cannot be deleted.'));
        }
        $booking->delete();

        return redirect()
            ->route('bookings.index')
            ->with('success', ui_phrase('Booking deleted successfully.'));
    }

    public function bookServiceItem(Request $request, Booking $booking, QuotationItem $quotationItem)
    {
        if (! $this->canManageBooking($booking, 'update')) {
            return $this->denyBookingMutation($booking);
        }
        if ($booking->isFinal()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking is final and cannot be edited.'));
        }
        if ((int) $quotationItem->quotation_id !== (int) $booking->quotation_id) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Selected service item does not belong to this booking quotation.'));
        }

        $existingBookingItem = $booking->items()
            ->withCount('bookingLogs')
            ->where('quotation_item_id', $quotationItem->id)
            ->first();
        if ($existingBookingItem && (int) ($existingBookingItem->booking_logs_count ?? 0) > 0) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('success', ui_phrase('Service item is already booked.'));
        }

        $isTouristAttraction = class_basename((string) $quotationItem->serviceable_type) === 'TouristAttraction';
        $validated = $request->validate([
            'vendor_provider_item_name' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:255'],
            'contact_channel' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:50'],
            'contact_value' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:255'],
            'contacted_person_name' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:255'],
            'service_date' => ['required', 'date'],
            'confirmation_number' => ['nullable', 'string', 'max:255'],
            'pax_adult' => ['required', 'integer', 'min:0'],
            'pax_child' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($existingBookingItem) {
            $bookingItem = $existingBookingItem;
        } else {
            $qty = max(1, (int) ($quotationItem->qty ?? 1));
            $unitPrice = max(0, (float) ($quotationItem->unit_price ?? 0));
            $bookingItem = $booking->items()->create([
                'quotation_item_id' => $quotationItem->id,
                'description' => trim((string) ($quotationItem->description ?? '')),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'total' => $qty * $unitPrice,
                'serviceable_type' => $quotationItem->serviceable_type,
                'serviceable_id' => $quotationItem->serviceable_id,
                'day_number' => $quotationItem->day_number,
                'serviceable_meta' => is_array($quotationItem->serviceable_meta ?? null) ? $quotationItem->serviceable_meta : null,
                'notes' => null,
            ]);
        }

        $bookingItem->bookingLogs()->create([
            'booked_at' => now(),
            'vendor_provider_item_name' => trim((string) ($validated['vendor_provider_item_name'] ?? '')) ?: null,
            'contact_channel' => trim((string) ($validated['contact_channel'] ?? '')) ?: null,
            'contact_value' => trim((string) ($validated['contact_value'] ?? '')) ?: null,
            'contacted_person_name' => trim((string) ($validated['contacted_person_name'] ?? '')) ?: null,
            'service_date' => ! empty($validated['service_date']) ? $validated['service_date'] : null,
            'confirmation_number' => trim((string) ($validated['confirmation_number'] ?? '')) ?: null,
            'pax_adult' => max(0, (int) ($validated['pax_adult'] ?? 0)),
            'pax_child' => max(0, (int) ($validated['pax_child'] ?? 0)),
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            'created_by' => auth()->id(),
        ]);
        $bookingItem->loadMissing(['serviceable', 'booking.items', 'booking.quotation.inquiry.customer', 'booking.quotation.itinerary']);
        $this->voucherService->generateOrRefresh($bookingItem);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', ui_phrase('Service item booked successfully. Voucher generated automatically.'));
    }

    public function updateServiceItem(Request $request, Booking $booking, QuotationItem $quotationItem)
    {
        if (! $this->canManageBooking($booking, 'update')) {
            return $this->denyBookingMutation($booking);
        }
        if ($booking->isFinal()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking is final and cannot be edited.'));
        }
        if ((int) $quotationItem->quotation_id !== (int) $booking->quotation_id) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Selected service item does not belong to this booking quotation.'));
        }

        $bookingItem = $booking->items()
            ->with(['bookingLogs' => fn ($q) => $q->latest('booked_at')])
            ->where('quotation_item_id', $quotationItem->id)
            ->first();

        if (! $bookingItem) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Service item booking data was not found.'));
        }

        $latestLog = $bookingItem->bookingLogs->first();
        if (! $latestLog) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Service item booking data was not found.'));
        }

        $isTouristAttraction = class_basename((string) $quotationItem->serviceable_type) === 'TouristAttraction';
        $validated = $request->validate([
            'vendor_provider_item_name' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:255'],
            'contact_channel' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:50'],
            'contact_value' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:255'],
            'contacted_person_name' => [$isTouristAttraction ? 'nullable' : 'required', 'string', 'max:255'],
            'service_date' => ['required', 'date'],
            'confirmation_number' => ['nullable', 'string', 'max:255'],
            'pax_adult' => ['required', 'integer', 'min:0'],
            'pax_child' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $latestLog->update([
            'vendor_provider_item_name' => trim((string) ($validated['vendor_provider_item_name'] ?? '')) ?: null,
            'contact_channel' => trim((string) ($validated['contact_channel'] ?? '')) ?: null,
            'contact_value' => trim((string) ($validated['contact_value'] ?? '')) ?: null,
            'contacted_person_name' => trim((string) ($validated['contacted_person_name'] ?? '')) ?: null,
            'service_date' => ! empty($validated['service_date']) ? $validated['service_date'] : null,
            'confirmation_number' => trim((string) ($validated['confirmation_number'] ?? '')) ?: null,
            'pax_adult' => max(0, (int) ($validated['pax_adult'] ?? 0)),
            'pax_child' => max(0, (int) ($validated['pax_child'] ?? 0)),
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
        ]);

        $bookingItem->loadMissing(['serviceable', 'booking.items', 'booking.quotation.inquiry.customer', 'booking.quotation.itinerary']);
        $this->voucherService->generateOrRefresh($bookingItem);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', ui_phrase('Service item booking updated successfully.'));
    }

    private function canManageBooking(Booking $booking, string $ability = 'update'): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (! in_array($ability, ['update', 'delete'], true)) {
            $ability = 'update';
        }

        return $user->can($ability, $booking);
    }

    private function denyBookingMutation(Booking $booking)
    {
        return redirect()
            ->route('bookings.show', $booking)
            ->with('error', 'You do not have permission to modify this booking.');
    }

    public function exportCsv(): StreamedResponse
    {
        $query = Booking::query()->with(['quotation.inquiry.customer']);

        $this->applyBookingIndexFilters($query);

        $bookings = $query->latest()->get();

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'booking_number',
                'quotation_number',
                'customer_name',
                'travel_date',
                'status',
                'created_at',
            ]);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->booking_number,
                    $booking->quotation->quotation_number ?? '',
                    $booking->quotation?->inquiry?->customer?->name ?? '',
                    optional($booking->travel_date)->format('Y-m-d'),
                    $booking->status,
                    optional($booking->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 'bookings-export.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function generateBookingNumber(): string
    {
        do {
            $number = 'BK-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Booking::query()->where('booking_number', $number)->exists());

        return $number;
    }

    private function eligibleQuotationQuery(?int $ignoreBookingId = null)
    {
        return Quotation::query()
            ->with(['inquiry.customer', 'items'])
            ->whereIn('status', ['approved', Quotation::FINAL_STATUS])
            ->whereHas('items')
            ->whereDoesntHave('items', function ($itemQuery) {
                $itemQuery->where(function ($q) {
                    $q->whereNull('is_validated')
                        ->orWhere('is_validated', false);
                });
            })
            ->where(function ($q) use ($ignoreBookingId) {
                if ($ignoreBookingId) {
                    $q->whereDoesntHave('booking')
                        ->orWhereHas('booking', fn ($bookingQ) => $bookingQ->whereKey($ignoreBookingId));

                    return;
                }

                $q->whereDoesntHave('booking');
            })
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at');
    }

    private function syncBookingItems(Booking $booking, array $items): void
    {
        $rows = collect($items)
            ->map(function ($item): array {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));

                return [
                    'quotation_item_id' => !empty($item['quotation_item_id']) ? (int) $item['quotation_item_id'] : null,
                    'description' => trim((string) ($item['description'] ?? '')),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'total' => $qty * $unitPrice,
                    'serviceable_type' => $item['serviceable_type'] ?? null,
                    'serviceable_id' => !empty($item['serviceable_id']) ? (int) $item['serviceable_id'] : null,
                    'day_number' => !empty($item['day_number']) ? (int) $item['day_number'] : null,
                    'serviceable_meta' => is_array($item['serviceable_meta'] ?? null) ? $item['serviceable_meta'] : null,
                    'notes' => trim((string) ($item['notes'] ?? '')) ?: null,
                ];
            })
            ->filter(fn (array $row) => $row['description'] !== '')
            ->values();

        $booking->items()->delete();
        if ($rows->isNotEmpty()) {
            $booking->items()->createMany($rows->all());
            $booking->load(['items.serviceable', 'quotation.inquiry.customer', 'quotation.itinerary']);
            foreach ($booking->items as $bookingItem) {
                $this->voucherService->generateOrRefresh($bookingItem);
            }
        }
    }

    private function resolveAutoStatus(string $travelDate, ?string $currentStatus = null): string
    {
        if (in_array((string) $currentStatus, [Booking::FINAL_STATUS, 'rejected'], true)) {
            return (string) $currentStatus;
        }

        if (trim($travelDate) === '') {
            return 'draft';
        }

        $today = now()->toDateString();
        if ($travelDate > $today) {
            return 'pending';
        }
        if ($travelDate === $today) {
            return 'approved';
        }

        return 'processed';
    }

    private function buildBookingSidebarInfo(\Illuminate\Database\Eloquent\Builder $filteredQuery): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $next7Days = now()->addDays(7)->toDateString();

        $statusCounts = (clone $filteredQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $focus = $this->resolveRoleAwareBookingFocus($filteredQuery, $today, $yesterday);

        return [
            'total' => (clone $filteredQuery)->count(),
            'travel_today' => (clone $filteredQuery)->whereDate('travel_date', $today)->count(),
            'upcoming_7_days' => (clone $filteredQuery)
                ->whereDate('travel_date', '>', $today)
                ->whereDate('travel_date', '<=', $next7Days)
                ->count(),
            'pending_past_travel_date' => (clone $filteredQuery)
                ->whereDate('travel_date', '<', $today)
                ->whereIn('status', ['draft', 'pending'])
                ->count(),
            'status_counts' => [
                'draft' => (int) ($statusCounts['draft'] ?? 0),
                'processed' => (int) ($statusCounts['processed'] ?? 0),
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'approved' => (int) ($statusCounts['approved'] ?? 0),
                'rejected' => (int) ($statusCounts['rejected'] ?? 0),
                'final' => (int) ($statusCounts[Booking::FINAL_STATUS] ?? 0),
            ],
            'focus' => $focus,
        ];
    }

    private function resolveRoleAwareBookingFocus(\Illuminate\Database\Eloquent\Builder $filteredQuery, string $today, string $yesterday): array
    {
        $user = auth()->user();
        if (! $user) {
            return [
                'title' => 'Operational Focus',
                'items' => [],
            ];
        }

        if ($user->can('dashboard.reservation.view')) {
            return [
                'title' => 'Reservation Focus',
                'items' => [
                    [
                        'label' => 'Travel Today',
                        'value' => (clone $filteredQuery)->whereDate('travel_date', $today)->where('status', '!=', Booking::FINAL_STATUS)->count(),
                    ],
                    [
                        'label' => 'Travel H-1',
                        'value' => (clone $filteredQuery)->whereDate('travel_date', $yesterday)->where('status', '!=', Booking::FINAL_STATUS)->count(),
                    ],
                ],
            ];
        }

        if ($user->can('dashboard.manager.view')) {
            return [
                'title' => 'Manager Focus',
                'items' => [
                    [
                        'label' => 'Pending Aging >= 2 Days',
                        'value' => (clone $filteredQuery)
                            ->where('status', 'pending')
                            ->whereDate('updated_at', '<=', now()->subDays(2)->toDateString())
                            ->count(),
                    ],
                    [
                        'label' => 'Draft Aging >= 2 Days',
                        'value' => (clone $filteredQuery)
                            ->where('status', 'draft')
                            ->whereDate('updated_at', '<=', now()->subDays(2)->toDateString())
                            ->count(),
                    ],
                ],
            ];
        }

        if ($user->can('dashboard.finance.view')) {
            return [
                'title' => 'Finance Focus',
                'items' => [
                    [
                        'label' => 'Not Final Yet',
                        'value' => (clone $filteredQuery)->where('status', '!=', Booking::FINAL_STATUS)->count(),
                    ],
                    [
                        'label' => 'Approved, Not Final',
                        'value' => (clone $filteredQuery)
                            ->where('status', 'approved')
                            ->count(),
                    ],
                ],
            ];
        }

        return [
            'title' => 'Operational Focus',
            'items' => [
                [
                    'label' => 'Pending',
                    'value' => (clone $filteredQuery)->where('status', 'pending')->count(),
                ],
                [
                    'label' => 'Not Final Yet',
                    'value' => (clone $filteredQuery)->where('status', '!=', Booking::FINAL_STATUS)->count(),
                ],
            ],
        ];
    }

    private function applyBookingIndexFilters(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where('booking_number', 'like', "%{$term}%")
                ->orWhereHas('quotation', function ($quo) use ($term) {
                    $quo->where('quotation_number', 'like', "%{$term}%")
                        ->orWhereHas('inquiry.customer', function ($c) use ($term) {
                            $c->where('name', 'like', "%{$term}%");
                        });
                });
        });

        $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        $query->when(request('quotation_id'), fn ($q) => $q->where('quotation_id', request('quotation_id')));
        $query->when(request('quotation'), function ($q) {
            $term = trim((string) request('quotation'));
            if ($term === '') {
                return;
            }

            $q->whereHas('quotation', function ($quotationQ) use ($term) {
                $quotationQ
                    ->where('quotation_number', 'like', '%' . $term . '%')
                    ->orWhere('order_number', 'like', '%' . $term . '%')
                    ->orWhereHas('inquiry.customer', fn ($customerQ) => $customerQ->where('name', 'like', '%' . $term . '%'));
            });
        });
        $query->when(request('order_number'), function ($q) {
            $term = trim((string) request('order_number'));
            if ($term === '') {
                return;
            }

            $q->whereHas('quotation', fn ($quotationQ) => $quotationQ->where('order_number', 'like', '%' . $term . '%'));
        });
        $query->when(request('travel_from'), fn ($q) => $q->whereDate('travel_date', '>=', request('travel_from')));
        $query->when(request('travel_to'), fn ($q) => $q->whereDate('travel_date', '<=', request('travel_to')));
    }
}
