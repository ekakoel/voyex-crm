<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\QuotationItem;
use App\Models\Quotation;
use App\Models\BookingItem;
use App\Models\ServiceCancellationPolicy;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\IslandTransfer;
use App\Models\TransportUnit;
use App\Models\HotelRoom;
use App\Models\Hotel;
use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Support\CompanySettingsCache;
use App\Services\BookingSnapshotService;
use App\Services\BookingVoucherService;
use App\Services\InvoiceService;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly BookingVoucherService $voucherService,
        private readonly BookingSnapshotService $bookingSnapshotService
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
        $this->loadQuotationServiceableRelations($quotations);

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
        $this->applyBookingSnapshotPayload($validated);
        $validated['status'] = $this->resolveAutoStatus((string) ($validated['travel_date'] ?? ''));
        $validated['booking_number'] = $this->generateBookingNumber();

        $booking = DB::transaction(function () use ($validated, $items): Booking {
            $booking = Booking::query()->create($validated);
            $this->syncBookingItems($booking, $items);
            $this->invoiceService->generateForBooking($booking);

            return $booking;
        });

        return redirect()
            ->route('bookings.edit', $booking)
            ->with('success', ui_phrase('Booking created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        $booking->load([
            'quotation.inquiry.customer',
            'quotation.inquiry.creator',
            'quotation.itinerary.destination',
            'quotation.items',
            'items.serviceable',
            'items.voucher',
            'items.latestBookingLog.creator',
            'items.vendorConfirmer',
            'invoices.payments',
            'adjustments.generatedInvoice',
            'settlement.reviewer',
            'settlement.finalizer',
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

    public function showSpk(Booking $booking)
    {
        if (! request()->user()?->can('bookings.operation.spk.view')) {
            abort(403);
        }

        $booking->load([
            'quotation.inquiry.customer',
            'items.serviceable',
            'items.latestBookingLog',
        ]);
        $booking->logActivity('operation.spk_viewed', $booking, [
            'booking_id' => $booking->id,
        ]);

        return view('modules.bookings.spk', compact('booking'));
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
        $this->loadQuotationServiceableRelations($quotations);
        $booking->load([
            'quotation.inquiry.customer',
            'quotation.items.serviceable',
            'items.serviceable',
            'items.voucher',
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
        foreach ($booking->items as $item) {
            $sourceUpdatedMap[$item->id] = $this->voucherService->isSourceUpdated($item);
        }
        $fallbackPolicyRulesMap = $this->buildFallbackPolicyRulesMap($booking);

        $hasOperationalLock = $booking->items->contains(function ($item) {
            return $item->voucher !== null || $item->latestBookingLog !== null;
        });

        $company = CompanySettingsCache::get();
        $companyName = trim((string) ($company?->company_name ?: 'BALI KAMI TOURS'));
        $companyAddress = collect([
            $company?->address ?? null,
            $company?->city ?? null,
            $company?->province ?? null,
        ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');
        $companyEmail = trim((string) ($company?->contact_email ?? ''));

        return view('modules.bookings.edit', compact(
            'booking',
            'quotations',
            'hasOperationalLock',
            'sourceUpdatedMap',
            'fallbackPolicyRulesMap',
            'companyName',
            'companyAddress',
            'companyEmail'
        ));
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
        $booking->load(['items.voucher', 'items.latestBookingLog']);
        $hasOperationalLock = $booking->items->contains(function ($item) {
            return $item->voucher !== null || $item->latestBookingLog !== null;
        });

        if ($hasOperationalLock && isset($validated['quotation_id']) && (int) $validated['quotation_id'] !== (int) $booking->quotation_id) {
            return redirect()
                ->route('bookings.edit', $booking)
                ->with('error', ui_phrase('Quotation cannot be changed because booking service and voucher history already exists.'));
        }

        if ($hasOperationalLock) {
            $validated['quotation_id'] = $booking->quotation_id;
        }
        $this->applyBookingSnapshotPayload($validated);
        $validated['status'] = $this->resolveAutoStatus((string) ($validated['travel_date'] ?? ''), (string) $booking->status);
        DB::transaction(function () use ($booking, $validated, $hasOperationalLock, $items): void {
            $booking->update($validated);
            if (! $hasOperationalLock) {
                $this->syncBookingItems($booking, $items);
            }
            $this->invoiceService->generateForBooking($booking);
        });

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

    public function cancel(Booking $booking)
    {
        if (! $this->canManageBooking($booking, 'update')) {
            return $this->denyBookingMutation($booking);
        }
        if ($booking->isFinal()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking is final and cannot be cancelled.'));
        }

        $invoiceStatus = (string) ($booking->invoice?->status ?? '');
        if (in_array($invoiceStatus, ['partially_paid', 'paid', 'overpaid'], true)) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking cannot be cancelled because invoice already has payment records.'));
        }

        if ((string) ($booking->status ?? '') === 'cancelled') {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('success', ui_phrase('Booking is already cancelled.'));
        }

        $booking->update(['status' => 'cancelled']);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', ui_phrase('Booking cancelled successfully.'));
    }

    public function close(Booking $booking)
    {
        return redirect()
            ->route('bookings.settlement.show', $booking)
            ->with('error', ui_phrase('Close booking is controlled by settlement gate. Review settlement first.'));
    }

    public function markReadyToOperate(Request $request, Booking $booking)
    {
        if (! $request->user()?->can('bookings.operation.prepare')) {
            abort(403);
        }
        if ($booking->isFinal() || (string) ($booking->status ?? '') === 'cancelled') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking is final and cannot be edited.'));
        }
        if (! in_array((string) ($booking->status ?? ''), ['confirmed', 'awaiting_dp', 'dp_received', 'awaiting_balance'], true)) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking status is not eligible to be marked as ready to operate.'));
        }
        if (! $this->isOperationPaymentSatisfied($booking)) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', ui_phrase('Booking cannot be marked ready to operate because payment requirement is not satisfied.'));
        }

        $fromStatus = (string) ($booking->status ?? '');
        $booking->update(['status' => 'ready_to_operate']);
        $booking->logActivity('operation.ready_to_operate', $booking, [
            'from_status' => $fromStatus,
            'to_status' => 'ready_to_operate',
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Booking marked as ready to operate.'));
    }

    public function startOperation(Request $request, Booking $booking)
    {
        if (! $request->user()?->can('bookings.operation.start')) {
            abort(403);
        }
        if ((string) ($booking->status ?? '') !== 'ready_to_operate') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', ui_phrase('Only ready to operate booking can start operation.'));
        }

        $fromStatus = (string) ($booking->status ?? '');
        $booking->update(['status' => 'in_operation']);
        $booking->logActivity('operation.started', $booking, [
            'from_status' => $fromStatus,
            'to_status' => 'in_operation',
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Booking operation has started.'));
    }

    public function completeService(Request $request, Booking $booking)
    {
        if (! $request->user()?->can('bookings.operation.complete')) {
            abort(403);
        }
        if ((string) ($booking->status ?? '') !== 'in_operation') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', ui_phrase('Only in operation booking can be marked as service completed.'));
        }

        $fromStatus = (string) ($booking->status ?? '');
        $booking->update(['status' => 'service_completed']);
        $booking->logActivity('operation.service_completed', $booking, [
            'from_status' => $fromStatus,
            'to_status' => 'service_completed',
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Booking service marked as completed.'));
    }

    public function reportOperationIssue(Request $request, Booking $booking)
    {
        if (! $request->user()?->can('bookings.operation.issue')) {
            abort(403);
        }

        $validated = $request->validate([
            'issue_note' => ['required', 'string', 'max:5000'],
            'booking_item_id' => ['nullable', 'integer'],
        ]);

        $bookingItemId = (int) ($validated['booking_item_id'] ?? 0);
        if ($bookingItemId > 0 && ! $booking->items()->whereKey($bookingItemId)->exists()) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', ui_phrase('Selected booking item is not part of this booking.'));
        }

        $booking->logActivity('operation.issue_reported', $booking, [
            'issue_note' => trim((string) $validated['issue_note']),
            'booking_item_id' => $bookingItemId > 0 ? $bookingItemId : null,
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Operation issue has been reported.'));
    }

    public function confirmItemVendor(Request $request, Booking $booking, BookingItem $bookingItem)
    {
        if (! $request->user()?->can('bookings.operation.vendor_confirm')) {
            abort(403);
        }
        if ((int) $bookingItem->booking_id !== (int) $booking->id) {
            abort(404);
        }

        $bookingItem->update([
            'vendor_confirmation_status' => BookingItem::VENDOR_CONFIRMATION_CONFIRMED,
            'vendor_confirmed_at' => now(),
            'vendor_confirmed_by' => auth()->id(),
        ]);
        $booking->logActivity('operation.item_vendor_confirmed', $booking, [
            'booking_item_id' => $bookingItem->id,
            'description' => $bookingItem->description,
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Vendor confirmation has been recorded.'));
    }

    public function updateItemDispatch(Request $request, Booking $booking, BookingItem $bookingItem)
    {
        if (! $request->user()?->can('bookings.operation.dispatch')) {
            abort(403);
        }
        if ((int) $bookingItem->booking_id !== (int) $booking->id) {
            abort(404);
        }

        $validated = $request->validate([
            'operation_notes' => ['nullable', 'string', 'max:5000'],
            'assigned_driver_name' => ['nullable', 'string', 'max:255'],
            'assigned_driver_phone' => ['nullable', 'string', 'max:255'],
            'assigned_guide_name' => ['nullable', 'string', 'max:255'],
            'assigned_guide_phone' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $request->user()?->can('bookings.operation.assign_driver')) {
            unset($validated['assigned_driver_name'], $validated['assigned_driver_phone']);
        }
        if (! $request->user()?->can('bookings.operation.assign_guide')) {
            unset($validated['assigned_guide_name'], $validated['assigned_guide_phone']);
        }

        $before = [
            'assigned_driver_name' => (string) ($bookingItem->assigned_driver_name ?? ''),
            'assigned_driver_phone' => (string) ($bookingItem->assigned_driver_phone ?? ''),
            'assigned_guide_name' => (string) ($bookingItem->assigned_guide_name ?? ''),
            'assigned_guide_phone' => (string) ($bookingItem->assigned_guide_phone ?? ''),
            'operation_notes' => (string) ($bookingItem->operation_notes ?? ''),
        ];
        $bookingItem->update($validated);
        $booking->logActivity('operation.item_dispatch_updated', $booking, [
            'booking_item_id' => $bookingItem->id,
            'changes' => $validated,
        ]);
        if (
            (($validated['assigned_driver_name'] ?? '') !== '' || ($validated['assigned_driver_phone'] ?? '') !== '')
            && (
                ($before['assigned_driver_name'] ?? '') !== (string) ($bookingItem->assigned_driver_name ?? '')
                || ($before['assigned_driver_phone'] ?? '') !== (string) ($bookingItem->assigned_driver_phone ?? '')
            )
        ) {
            $booking->logActivity('operation.item_driver_assigned', $booking, [
                'booking_item_id' => $bookingItem->id,
                'driver_name' => $bookingItem->assigned_driver_name,
                'driver_phone' => $bookingItem->assigned_driver_phone,
            ]);
        }
        if (
            (($validated['assigned_guide_name'] ?? '') !== '' || ($validated['assigned_guide_phone'] ?? '') !== '')
            && (
                ($before['assigned_guide_name'] ?? '') !== (string) ($bookingItem->assigned_guide_name ?? '')
                || ($before['assigned_guide_phone'] ?? '') !== (string) ($bookingItem->assigned_guide_phone ?? '')
            )
        ) {
            $booking->logActivity('operation.item_guide_assigned', $booking, [
                'booking_item_id' => $bookingItem->id,
                'guide_name' => $bookingItem->assigned_guide_name,
                'guide_phone' => $bookingItem->assigned_guide_phone,
            ]);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Dispatch information has been updated.'));
    }

    public function markItemDispatchReady(Request $request, Booking $booking, BookingItem $bookingItem)
    {
        if (! $request->user()?->can('bookings.operation.dispatch')) {
            abort(403);
        }
        if ((int) $bookingItem->booking_id !== (int) $booking->id) {
            abort(404);
        }

        $bookingItem->update([
            'dispatch_status' => BookingItem::DISPATCH_READY,
        ]);
        $booking->logActivity('operation.item_ready', $booking, [
            'booking_item_id' => $bookingItem->id,
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Booking item marked as ready.'));
    }

    public function markItemDispatchCompleted(Request $request, Booking $booking, BookingItem $bookingItem)
    {
        if (! $request->user()?->can('bookings.operation.dispatch')) {
            abort(403);
        }
        if ((int) $bookingItem->booking_id !== (int) $booking->id) {
            abort(404);
        }

        $bookingItem->update([
            'dispatch_status' => BookingItem::DISPATCH_COMPLETED,
        ]);
        $booking->logActivity('operation.item_completed', $booking, [
            'booking_item_id' => $bookingItem->id,
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Booking item marked as completed.'));
    }

    public function reportItemDispatchIssue(Request $request, Booking $booking, BookingItem $bookingItem)
    {
        if (! $request->user()?->can('bookings.operation.dispatch')) {
            abort(403);
        }
        if ((int) $bookingItem->booking_id !== (int) $booking->id) {
            abort(404);
        }

        $validated = $request->validate([
            'issue_note' => ['required', 'string', 'max:5000'],
        ]);

        $bookingItem->update([
            'dispatch_status' => BookingItem::DISPATCH_ISSUE,
            'issue_note' => trim((string) $validated['issue_note']),
        ]);
        $booking->logActivity('operation.item_issue_reported', $booking, [
            'booking_item_id' => $bookingItem->id,
            'issue_note' => $bookingItem->issue_note,
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', ui_phrase('Booking item issue has been reported.'));
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
                ->route('bookings.edit', $booking)
                ->with('error', ui_phrase('Selected service item does not belong to this booking quotation.'));
        }

        $existingBookingItem = $booking->items()
            ->withCount('bookingLogs')
            ->where('quotation_item_id', $quotationItem->id)
            ->first();
        if ($existingBookingItem && (int) ($existingBookingItem->booking_logs_count ?? 0) > 0) {
            return redirect()
                ->route('bookings.edit', $booking)
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
        ]);

        if ($existingBookingItem) {
            $bookingItem = $existingBookingItem;
            if (empty($bookingItem->cancellation_policy_snapshot)) {
                $bookingItem->update([
                    'cancellation_policy_snapshot' => $this->buildCancellationPolicySnapshot($quotationItem),
                ]);
            }
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
                'cancellation_policy_snapshot' => $this->buildCancellationPolicySnapshot($quotationItem),
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
            'created_by' => auth()->id(),
        ]);
        $bookingItem->update([
            'status' => BookingItem::STATUS_BOOKED,
            'cancellation_fee' => 0,
            'cancelled_at' => null,
        ]);
        $bookingItem->loadMissing(['serviceable', 'booking.items', 'booking.quotation.inquiry.customer', 'booking.quotation.itinerary']);
        $this->voucherService->generateOrRefresh($bookingItem);
        $this->syncProviderContactFromBookingInput($quotationItem, $validated);

        return redirect()
            ->route('bookings.edit', $booking)
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
                ->route('bookings.edit', $booking)
                ->with('error', ui_phrase('Selected service item does not belong to this booking quotation.'));
        }

        $bookingItem = $booking->items()
            ->with(['bookingLogs' => fn ($q) => $q->latest('booked_at')])
            ->where('quotation_item_id', $quotationItem->id)
            ->first();

        if (! $bookingItem) {
            return redirect()
                ->route('bookings.edit', $booking)
                ->with('error', ui_phrase('Service item booking data was not found.'));
        }

        $latestLog = $bookingItem->bookingLogs->first();
        if (! $latestLog) {
            return redirect()
                ->route('bookings.edit', $booking)
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
        ]);
        if ((string) ($bookingItem->status ?? '') === BookingItem::STATUS_CANCELLED) {
            $bookingItem->update([
                'status' => BookingItem::STATUS_BOOKED,
                'cancellation_fee' => 0,
                'cancellation_fee_calculated' => 0,
                'cancellation_fee_overridden' => false,
                'cancelled_at' => null,
            ]);
        }

        $bookingItem->loadMissing(['serviceable', 'booking.items', 'booking.quotation.inquiry.customer', 'booking.quotation.itinerary']);
        $this->voucherService->generateOrRefresh($bookingItem);
        $this->syncProviderContactFromBookingInput($quotationItem, $validated);

        return redirect()
            ->route('bookings.edit', $booking)
            ->with('success', ui_phrase('Service item booking updated successfully.'));
    }

    public function cancelServiceItem(Request $request, Booking $booking, QuotationItem $quotationItem)
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
                ->route('bookings.edit', $booking)
                ->with('error', ui_phrase('Selected service item does not belong to this booking quotation.'));
        }

        $bookingItem = $booking->items()
            ->where('quotation_item_id', $quotationItem->id)
            ->first();

        if (! $bookingItem) {
            return redirect()
                ->route('bookings.edit', $booking)
                ->with('error', ui_phrase('Service item booking data was not found.'));
        }

        $validated = $request->validate([
            'cancellation_fee_type' => ['required', 'in:nominal,percent'],
            'cancellation_fee' => ['required', 'numeric', 'min:0'],
            'cancellation_policy_text' => ['nullable', 'string'],
        ]);

        $feeType = (string) ($validated['cancellation_fee_type'] ?? 'nominal');
        $feeInput = max(0, (float) ($validated['cancellation_fee'] ?? 0));
        $itemTotal = max(0, (float) ($bookingItem->total ?? 0));
        $finalFee = $feeType === 'percent'
            ? round($itemTotal * ($feeInput / 100), 2)
            : round($this->displayCurrencyToIdr($feeInput), 2);

        $bookingItem->update([
            'status' => BookingItem::STATUS_CANCELLED,
            'cancellation_fee' => $finalFee,
            'cancellation_fee_calculated' => $finalFee,
            'cancellation_fee_overridden' => true,
            'cancelled_at' => now(),
        ]);
        $this->persistServiceCancellationPolicyTextIfMissing(
            $quotationItem,
            trim((string) ($validated['cancellation_policy_text'] ?? ''))
        );
        $this->persistCancellationPolicyDefaultIfMissing($quotationItem, $feeType, $feeInput);

        return redirect()
            ->route('bookings.edit', $booking)
            ->with('success', ui_phrase('Service item cancelled successfully.'));
    }

    private function persistCancellationPolicyDefaultIfMissing(QuotationItem $quotationItem, string $feeType, float $feeInput): void
    {
        [$serviceableType, $serviceableId, $policyName] = $this->resolveCancellationPolicyTarget($quotationItem);
        if ($serviceableType === '' || $serviceableId <= 0) {
            return;
        }

        $existingPolicy = ServiceCancellationPolicy::query()
            ->withCount('rules')
            ->where('serviceable_type', $serviceableType)
            ->where('serviceable_id', $serviceableId)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($existingPolicy && (int) ($existingPolicy->rules_count ?? 0) > 0) {
            return;
        }

        $policy = $existingPolicy ?: new ServiceCancellationPolicy([
            'serviceable_type' => $serviceableType,
            'serviceable_id' => $serviceableId,
        ]);
        $policy->name = $policyName !== '' ? $policyName : ui_phrase('Default Cancellation Policy');
        $policy->is_active = true;
        $policy->save();

        $feeTypeNormalized = strtolower(trim($feeType)) === 'percent' ? 'percent' : 'fixed';
        $feeValue = $feeTypeNormalized === 'percent'
            ? round(max(0, $feeInput), 2)
            : round($this->displayCurrencyToIdr(max(0, $feeInput)), 2);

        $policy->rules()->delete();
        $policy->rules()->create([
            'min_days_before' => null,
            'max_days_before' => null,
            'fee_type' => $feeTypeNormalized,
            'fee_value' => $feeValue,
            'description' => ui_phrase('Default from booking cancellation input'),
            'sort_order' => 0,
        ]);
    }

    private function persistServiceCancellationPolicyTextIfMissing(QuotationItem $quotationItem, string $policyText): void
    {
        if ($policyText === '') {
            return;
        }

        $serviceable = $quotationItem->serviceable;
        if (! $serviceable) {
            return;
        }

        if ($serviceable instanceof HotelRoom) {
            $hotel = $serviceable->hotel;
            if ($hotel && trim((string) ($hotel->cancellation_policy ?? '')) === '') {
                $hotel->update(['cancellation_policy' => $policyText]);
            }

            return;
        }

        if (in_array('cancellation_policy', $serviceable->getFillable(), true)
            && trim((string) ($serviceable->cancellation_policy ?? '')) === '') {
            $serviceable->update(['cancellation_policy' => $policyText]);
        }
    }

    private function resolveCancellationPolicyTarget(QuotationItem $quotationItem): array
    {
        $serviceableType = (string) ($quotationItem->serviceable_type ?? '');
        $serviceableId = (int) ($quotationItem->serviceable_id ?? 0);
        $policyName = trim((string) ($quotationItem->description ?? ''));

        if ($serviceableType === '' || $serviceableId <= 0) {
            return ['', 0, $policyName];
        }

        if ($serviceableType === HotelRoom::class || class_basename($serviceableType) === 'HotelRoom') {
            $room = HotelRoom::query()->with('hotel:id,name')->find($serviceableId);
            $hotelId = (int) ($room?->hotel?->id ?? 0);
            if ($hotelId > 0) {
                $serviceableType = (new Hotel())->getMorphClass();
                $serviceableId = $hotelId;
                $policyName = trim((string) ($room?->hotel?->name ?? $policyName));
            }
        }

        return [$serviceableType, $serviceableId, $policyName];
    }

    private function buildCancellationPolicySnapshot(QuotationItem $quotationItem): ?array
    {
        [$serviceableType, $serviceableId] = $this->resolveCancellationPolicyTarget($quotationItem);
        if ($serviceableType === '' || $serviceableId <= 0) {
            return null;
        }

        $policy = ServiceCancellationPolicy::query()
            ->with('rules')
            ->where('serviceable_type', $serviceableType)
            ->where('serviceable_id', $serviceableId)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (! $policy) {
            return null;
        }

        return [
            'policy_id' => (int) $policy->id,
            'name' => (string) ($policy->name ?? ''),
            'rules' => $policy->rules->map(fn ($rule) => [
                'min_days_before' => $rule->min_days_before,
                'max_days_before' => $rule->max_days_before,
                'fee_type' => (string) ($rule->fee_type ?? 'fixed'),
                'fee_value' => (float) ($rule->fee_value ?? 0),
                'description' => (string) ($rule->description ?? ''),
                'sort_order' => (int) ($rule->sort_order ?? 0),
            ])->values()->all(),
        ];
    }

    private function calculateCancellationFee(BookingItem $bookingItem, $serviceDate): float
    {
        if (! $serviceDate) {
            return 0;
        }

        $serviceDateValue = $serviceDate instanceof Carbon ? $serviceDate->copy() : Carbon::parse((string) $serviceDate);
        $today = now()->startOfDay();
        $daysBefore = $today->diffInDays($serviceDateValue->startOfDay(), false);
        $rules = collect($bookingItem->cancellation_policy_snapshot['rules'] ?? []);
        if ($rules->isEmpty()) {
            return 0;
        }

        $matchedRule = $rules->first(function (array $rule) use ($daysBefore) {
            $min = array_key_exists('min_days_before', $rule) && $rule['min_days_before'] !== null ? (int) $rule['min_days_before'] : null;
            $max = array_key_exists('max_days_before', $rule) && $rule['max_days_before'] !== null ? (int) $rule['max_days_before'] : null;

            if ($min !== null && $daysBefore < $min) {
                return false;
            }
            if ($max !== null && $daysBefore > $max) {
                return false;
            }

            return true;
        });

        if (! is_array($matchedRule)) {
            return 0;
        }

        $feeType = strtolower((string) ($matchedRule['fee_type'] ?? 'fixed'));
        $feeValue = max(0, (float) ($matchedRule['fee_value'] ?? 0));
        $itemTotal = max(0, (float) ($bookingItem->total ?? 0));

        if ($feeType === 'percent') {
            return round($itemTotal * ($feeValue / 100), 2);
        }

        return round($feeValue, 2);
    }

    private function syncProviderContactFromBookingInput(QuotationItem $quotationItem, array $validated): void
    {
        $serviceable = $quotationItem->serviceable;
        if (! $serviceable) {
            return;
        }

        $channel = strtolower(trim((string) ($validated['contact_channel'] ?? '')));
        $contactValue = trim((string) ($validated['contact_value'] ?? ''));
        $contactPerson = trim((string) ($validated['contacted_person_name'] ?? ''));

        if (method_exists($serviceable, 'vendor') && $serviceable->vendor) {
            $vendor = $serviceable->vendor;
            $patch = [];
            if ($contactPerson !== '') {
                $patch['contact_name'] = $contactPerson;
            }
            if ($contactValue !== '') {
                if ($channel === 'email') {
                    $patch['contact_email'] = $contactValue;
                } elseif (in_array($channel, ['phone', 'whatsapp', 'wechat'], true)) {
                    $patch['contact_phone'] = $contactValue;
                } elseif ($channel === 'other' && filter_var($contactValue, FILTER_VALIDATE_URL)) {
                    $patch['website'] = $contactValue;
                }
            }
            if (! empty($patch)) {
                $vendor->update($patch);
            }

            return;
        }

        if (method_exists($serviceable, 'hotel') && $serviceable->hotel) {
            $hotel = $serviceable->hotel;
            $patch = [];
            if ($contactPerson !== '') {
                $patch['contact_person'] = $contactPerson;
            }
            if ($contactValue !== '') {
                if (in_array($channel, ['phone', 'whatsapp', 'wechat'], true)) {
                    $patch['phone'] = $contactValue;
                } elseif ($channel === 'other' && filter_var($contactValue, FILTER_VALIDATE_URL)) {
                    $patch['web'] = $contactValue;
                }
            }
            if (! empty($patch)) {
                $hotel->update($patch);
            }
        }
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
            ->with('error', ui_phrase('You do not have permission to modify this booking.'));
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
            ->whereIn('status', ['accepted', Quotation::FINAL_STATUS])
            ->where('validation_status', 'valid')
            ->whereHas('items')
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

    private function loadQuotationServiceableRelations(\Illuminate\Support\Collection $quotations): void
    {
        foreach ($quotations as $quotation) {
            $quotation->items->loadMorph('serviceable', [
                Activity::class => ['vendor'],
                FoodBeverage::class => ['vendor'],
                IslandTransfer::class => ['vendor'],
                TransportUnit::class => ['vendor'],
                HotelRoom::class => ['hotel'],
            ]);
        }
    }

    private function buildFallbackPolicyRulesMap(Booking $booking): array
    {
        $quotationItems = ($booking->quotation?->items ?? collect())
            ->filter(fn ($item) => (string) ($item->itinerary_item_type ?? '') !== 'manual')
            ->values();
        if ($quotationItems->isEmpty()) {
            return [];
        }

        $bookedItemsByQuotationItemId = $booking->items
            ->filter(fn ($item) => ! empty($item->quotation_item_id))
            ->keyBy('quotation_item_id');
        $policyTargets = [];
        foreach ($quotationItems as $quotationItem) {
            $mappedBookingItem = $bookedItemsByQuotationItemId->get($quotationItem->id);
            $mappedSnapshotRules = collect($mappedBookingItem?->cancellation_policy_snapshot['rules'] ?? []);
            if ($mappedSnapshotRules->isNotEmpty()) {
                continue;
            }

            $targetType = (string) ($quotationItem->serviceable_type ?? '');
            $targetId = (int) ($quotationItem->serviceable_id ?? 0);
            if ($targetType === '' || $targetId <= 0) {
                continue;
            }
            if ($targetType === HotelRoom::class || class_basename($targetType) === 'HotelRoom') {
                $hotelId = (int) ($quotationItem->serviceable?->hotel?->id ?? 0);
                if ($hotelId > 0) {
                    $targetType = (new Hotel())->getMorphClass();
                    $targetId = $hotelId;
                }
            }
            $policyTargets[$targetType . '#' . $targetId] = ['type' => $targetType, 'id' => $targetId];
        }

        if ($policyTargets === []) {
            return [];
        }

        $map = [];
        $fallbackPolicies = ServiceCancellationPolicy::query()
            ->with('rules')
            ->where('is_active', true)
            ->where(function ($query) use ($policyTargets) {
                foreach ($policyTargets as $target) {
                    $query->orWhere(function ($targetQuery) use ($target) {
                        $targetQuery
                            ->where('serviceable_type', (string) $target['type'])
                            ->where('serviceable_id', (int) $target['id']);
                    });
                }
            })
            ->latest('id')
            ->get()
            ->unique(fn ($policy) => (string) ($policy->serviceable_type ?? '') . '#' . (int) ($policy->serviceable_id ?? 0));

        foreach ($fallbackPolicies as $policy) {
            $mapKey = (string) ($policy->serviceable_type ?? '') . '#' . (int) ($policy->serviceable_id ?? 0);
            $map[$mapKey] = $policy->rules
                ->map(fn ($rule) => [
                    'min_days_before' => $rule->min_days_before,
                    'max_days_before' => $rule->max_days_before,
                    'fee_type' => (string) ($rule->fee_type ?? 'fixed'),
                    'fee_value' => (float) ($rule->fee_value ?? 0),
                    'description' => (string) ($rule->description ?? ''),
                ])
                ->values()
                ->all();
        }

        return $map;
    }

    private function syncBookingItems(Booking $booking, array $items): void
    {
        $rows = collect($items)
            ->map(function ($item): array {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $unitPriceDisplay = max(0, (float) ($item['unit_price'] ?? 0));
                $unitPrice = $this->displayCurrencyToIdr($unitPriceDisplay);

                return [
                    'quotation_item_id' => !empty($item['quotation_item_id']) ? (int) $item['quotation_item_id'] : null,
                    'description' => trim((string) ($item['description'] ?? '')),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'total' => $qty * $unitPrice,
                    'status' => BookingItem::STATUS_ACTIVE,
                    'cancellation_fee' => 0,
                    'cancellation_fee_calculated' => 0,
                    'cancellation_fee_overridden' => false,
                    'cancelled_at' => null,
                    'cancellation_policy_snapshot' => null,
                    'serviceable_type' => $item['serviceable_type'] ?? null,
                    'serviceable_id' => !empty($item['serviceable_id']) ? (int) $item['serviceable_id'] : null,
                    'day_number' => !empty($item['day_number']) ? (int) $item['day_number'] : null,
                    'serviceable_meta' => is_array($item['serviceable_meta'] ?? null) ? $item['serviceable_meta'] : null,
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
        if (in_array((string) $currentStatus, [Booking::FINAL_STATUS, 'cancelled'], true)) {
            return (string) $currentStatus;
        }
        if (trim((string) $currentStatus) !== '') {
            return (string) $currentStatus;
        }

        return 'confirmed';
    }

    private function isOperationPaymentSatisfied(Booking $booking): bool
    {
        $booking->loadMissing('invoices.payments');
        $invoices = $booking->invoices ?? collect();
        if ($invoices->isEmpty()) {
            return false;
        }

        foreach ($invoices as $invoice) {
            $status = (string) ($invoice->status ?? '');
            if (in_array($status, ['paid', 'overpaid'], true)) {
                return true;
            }

            $hasConfirmedPayment = $invoice->payments->contains(function ($payment) {
                return (string) ($payment->status ?? '') === 'confirmed';
            });
            if ($hasConfirmedPayment) {
                return true;
            }
        }

        return false;
    }

    private function evaluateSettlementReadiness(Booking $booking): array
    {
        if (! in_array((string) ($booking->status ?? ''), ['service_completed', 'completed_settled'], true)) {
            return [
                'ready' => false,
                'message' => ui_phrase('Booking can only be closed after service is completed.'),
            ];
        }

        $invoiceStatus = (string) ($booking->invoice?->status ?? '');
        if ($invoiceStatus === '') {
            return [
                'ready' => false,
                'message' => ui_phrase('Booking cannot be closed because invoice is not generated.'),
            ];
        }
        if (! in_array($invoiceStatus, ['paid', 'overpaid'], true)) {
            return [
                'ready' => false,
                'message' => ui_phrase('Booking cannot be closed because invoice is not settled yet.'),
            ];
        }

        $hasActiveItems = $booking->items->contains(fn ($item) => (string) ($item->status ?? '') !== BookingItem::STATUS_CANCELLED);
        if (! $hasActiveItems) {
            return [
                'ready' => false,
                'message' => ui_phrase('Booking cannot be closed because all service items are cancelled.'),
            ];
        }

        return [
            'ready' => true,
            'message' => '',
        ];
    }

    private function applyBookingSnapshotPayload(array &$validated): void
    {
        $quotationId = (int) ($validated['quotation_id'] ?? 0);
        if ($quotationId <= 0) {
            return;
        }

        $quotation = Quotation::query()
            ->with(['itinerary.destination'])
            ->find($quotationId);
        if (! $quotation) {
            return;
        }

        $pax = $this->bookingSnapshotService->resolvePaxSnapshot(
            $quotation,
            isset($validated['pax_adult']) ? (int) $validated['pax_adult'] : null,
            isset($validated['pax_child']) ? (int) $validated['pax_child'] : null
        );
        $validated['pax_adult'] = (int) $pax['pax_adult'];
        $validated['pax_child'] = (int) $pax['pax_child'];
        $validated['itinerary_snapshot'] = $this->bookingSnapshotService->resolveItinerarySnapshot($quotation);
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
                ->whereIn('status', ['pending_confirmation', 'awaiting_dp', 'awaiting_balance'])
                ->count(),
            'status_counts' => [
                'pending_confirmation' => (int) ($statusCounts['pending_confirmation'] ?? 0),
                'confirmed' => (int) ($statusCounts['confirmed'] ?? 0),
                'awaiting_dp' => (int) ($statusCounts['awaiting_dp'] ?? 0),
                'ready_to_operate' => (int) ($statusCounts['ready_to_operate'] ?? 0),
                'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
                'closed' => (int) ($statusCounts[Booking::FINAL_STATUS] ?? 0),
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
                            ->where('status', 'awaiting_dp')
                            ->whereDate('updated_at', '<=', now()->subDays(2)->toDateString())
                            ->count(),
                    ],
                    [
                        'label' => 'Draft Aging >= 2 Days',
                        'value' => (clone $filteredQuery)
                            ->where('status', 'pending_confirmation')
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
                            ->where('status', 'ready_to_operate')
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
                    'value' => (clone $filteredQuery)->where('status', 'awaiting_dp')->count(),
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
